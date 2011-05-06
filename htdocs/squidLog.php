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
    private $hitEntries;
    private $missEntries;

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

    private function addLogEntry($logLine) {
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

            //not finished
            if($entry["hier"] == "NONE") {
                $this->hitEntries[] = new squidLogEntry($entry);
            } else {
                $this->missEntries[] = new squidLogEntry($entry);
            }
        }
    }

    public function getHitEntries() {
        return $this->hitEntries;
    }

    public function getMissEntries() {
        return $this->missEntries;
    }

    /**
     * Does this log contain an request for the specified url?
     * @param string $url The url to check for
     * @return int <0, 0, or >0 if it was a missed request, was not requested, or was a hit request respectivly
     */
    public function hasURLRequest($url) {
        foreach($this->missEntries as $entry) {
            if($entry->url === $url) {
                return -1;
            }
        }

        foreach($this->hitEntries as $entry) {
            if($entry->url === $url) {
                return 1;
            }
        }

        return 0;
    }
}

?>
