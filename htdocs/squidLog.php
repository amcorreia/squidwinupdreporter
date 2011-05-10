<?php
require_once("squidLogEntry.php");

/**
 * This is an array of regex patterns that we will match URLs against to decide if they're windows update urls
 */
$windowsUpdatePatterns = array();
$windowsUpdatePatterns[] = 'windowsupdate.com/.*\.(cab|exe|dll|msi|psf)\s';
$windowsUpdatePatterns[] = 'download.microsoft.com/.*\.(cab|exe|dll|msi|psf)\s';
$windowsUpdatePatterns[] = 'www.microsoft.com/.*\.(cab|exe|dll|msi|psf)\s';
$windowsUpdatePatterns[] = 'au.download.windowsupdate.com/.*\.(cab|exe|dll|msi|psf)\s';

class squidLog {
    private $entries = array();

    public function __construct($logFileName) {
        if((($logFile = fopen(strval($logFileName), "r"))) === false) {
            throw(new RuntimeException("Could not open log file (" . strval($logFileName) . " for reading"));
        }

        while(($line = fgets($logFile)) !== false) {
            if($this->isWindowsUpdateLogLine($line)) {
                $this->addLogEntry($line);
            }
        }

        fclose($logFile);
    }

    private function isWindowsUpdateLogLine($logLine) {
        global $windowsUpdatePatterns;

        foreach($windowsUpdatePatterns as $pattern) {
            // this is taking the base pattern, escaping forward slashes,
            // and adding the leading and trailing forward slash
            if(preg_match("/" . addcslashes($pattern, "/") . "/", $logLine)) {
                return true;
            } else {
                return false;
            }
        }
    }

    private function parseLogEntry($logLine) {
        if (
            preg_match (
                '/([\d\.]+)(\s+)(\d+)(\s+)(((\d{1,3}\.){3})(\d{1,3}))' .
                '(\s+)(\w+)(\/)(\d+)(\s+)(\d+)(\s+)(\w+)(\s+)(\S+)' .
                '(\s+-\s+)(\w+)(\/)((((\d{1,3}\.){3})(\d{1,3}))|(-))' .
                '(\s+)([\w\/-]+)/',
                $logLine,
                $pieces
            )
        ) {
            $entry = array (
                "timestamp"    => $pieces[1],
                "elapsed"      => $pieces[3],
                "client"       => $pieces[5],
                "action"       => $pieces[10],
                "code"         => $pieces[12],
                "size"         => $pieces[14],
                "method"       => $pieces[16],
                "url"          => $pieces[18],
                "hier"         => $pieces[20],
                "from"         => $pieces[22],
                "content"      => $pieces[29],
            );

            return new squidLogEntry($entry);
        } else {
            return null;
        }
    }

    private function addLogEntry($logLine) {
        $newLogEntry = $this->parseLogEntry($logLine);

        if(is_null($newLogEntry)) {
            return;
        }

        $existingEntry = &$this->getEntryWithURL($newLogEntry->url);

        if(is_null($existingEntry)) {
            $this->addNewLogEntry($newLogEntry);
        } else {
            $this->mergeExistingLogEntry($newLogEntry, $existingEntry);
        }
    }

    private function addNewLogEntry($entry) {
        $this->entries[] = $entry;
    }

    private function mergeExistingLogEntry($newEntry, &$existingEntry) {
        $existingEntry = clone $newEntry;
    }

    public function getEntries() {
        return $this->entries;
    }

    /**
     * Return the squid log entry that has the specified url if there is one
     * @param string $url The url to check for
     * @return &squidLogEntry|NULL The squid log entry with the specified URL or NULL there isn't one
     */
    public function getEntryWithURL($url) {
        foreach($this->entries as $entry) {
            if($entry->url === $url) {
                return $entry;
            }
        }

        return NULL;
    }
}

?>
