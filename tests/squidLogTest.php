<?php
require_once("../htdocs/squidLog.php");

class squidLogTest extends PHPUnit_Framework_TestCase {
    private static function getTempLogFileName() {
        $tempDir = sys_get_temp_dir();
        return tempnam($tempDir, "phpunit");
    }

    private static function getSquidLogWithContents($logLine) {
        $logFileName = self::getTempLogFileName();
        file_put_contents($logFileName, $logLine);

        $mySquidLog = new squidLog($logFileName);
        unlink($logFileName);

        return $mySquidLog;
    }

    public function logLineProvider() {
        return array (
            array (
                '1304503682.589     49 192.168.1.190 TCP_MISS/200 1248 POST ' .        // log line text
                'http://safebrowsing.clients.google.com/safebrowsing/downloads?' .
                ' - DIRECT/74.125.47.113 application/vnd.google.safebrowsing-update',
                false,                                                                 // windows update?
                false                                                                  // hit?
            ),
            array (
                '1304506586.411     74 192.168.1.214 TCP_REFRESH_UNMODIFIED/304 327 GET ' .
                'http://mscrl.microsoft.com/pki/mscorp/crl/mswww(5).crl - DIRECT/65.54.80.227 application/pkix-crl',
                false,
                false,
            ),
            array (
                '1304537726.077      2 192.168.1.125 TCP_HIT/200 45011 GET ' .
                'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
                '- NONE/- application/octet-stream',
                true,
                true,
            ),
            array (
                '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
                'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
                '- DIRECT/- application/octet-stream',
                true,
                false,
            )
        );
    }

    public function testConstructNonExistentLog() {
        $this->setExpectedException("RuntimeException");
        $mySquidLog = new squidLog("/usr/testlog.log");
    }

    /**
     * @dataProvider logLineProvider
     */
    public function testIsWindowsUpdateLogLine($logLine, $isWindowsUpdate, $isHit) {
        $mySquidLog = self::getSquidLogWithContents($logLine);

        $expectedEntryCount = ($isWindowsUpdate)?(1):(0);

        $entryCount = count($mySquidLog->getEntries());
        $this->assertEquals($expectedEntryCount, $entryCount);
    }

    public function testAddLogLineParsing() {
        $mySquidLog = self::getSquidLogWithContents (
            '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
            '- DIRECT/- application/octet-stream'
        );

        $entries = $mySquidLog->getEntries();
        $entry = $entries[0];

        $this->assertEquals('1304537726.077',           $entry->timestamp);
        $this->assertEquals('2',                        $entry->elapsed);
        $this->assertEquals('192.168.1.125',            $entry->client);
        $this->assertEquals('TCP_REFRESH_UNMODIFIED',   $entry->action);
        $this->assertEquals('304',                      $entry->code);
        $this->assertEquals('45011',                    $entry->size);
        $this->assertEquals('GET',                      $entry->method);
        $this->assertEquals('DIRECT',                   $entry->hier);
        $this->assertEquals('-',                        $entry->from);
        $this->assertEquals('application/octet-stream', $entry->content);
        $this->assertEquals('http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab', $entry->url);
    }

    public function testAddLogLineNonDuplicateURLs() {
        $mySquidLog = self::getSquidLogWithContents (
            '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
            '- DIRECT/- application/octet-stream' .
            "\n" .
            '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v4/static/trustedr/en/othercab.exe '.
            '- DIRECT/- application/octet-stream'
        );

        $entries = $mySquidLog->getEntries();
        $this->assertEquals(2, count($entries));
        $this->assertNotEquals($entries[0]->url, $entries[1]->url);
    }

    public function testAddLogLineDuplicateURLs() {
        $mySquidLog = self::getSquidLogWithContents (
            '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
            '- DIRECT/- application/octet-stream' .
            "\n" .
            '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
            '- NONE/- application/octet-stream'
        );

        $entries = $mySquidLog->getEntries();
        $this->assertEquals(1, count($entries));
        $this->assertTrue($entries[0]->isHit());
    }

    public function testGetEntryWithURLForMissRequest() {
        $mySquidLog = self::getSquidLogWithContents (
            '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
            '- DIRECT/- application/octet-stream'
        );

        $entry = $mySquidLog->getEntryWithURL('http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab');
        $this->assertFalse($entry->isHit());
    }

    public function testGetEntryWithURLForNonExistentEntry() {
        $mySquidLog = self::getSquidLogWithContents (
            '1304537726.077      2 192.168.1.125 TCP_REFRESH_UNMODIFIED/304 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
            '- DIRECT/- application/octet-stream'
        );

        $this->assertNull($mySquidLog->getEntryWithURL('http://www.download.windowsupdate.com/msdownload/update/v3/'));
    }

    public function testGetEntryWithURLForHitRequest() {
        $mySquidLog = self::getSquidLogWithContents (
            '1304537726.077      2 192.168.1.125 TCP_HIT/200 45011 GET ' .
            'http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab '.
            '- NONE/- application/octet-stream'
        );

        $entry = $mySquidLog->getEntryWithURL('http://www.download.windowsupdate.com/msdownload/update/v3/static/trustedr/en/authrootstl.cab');
        $this->assertTrue($entry->isHit());
    }
}

?>
