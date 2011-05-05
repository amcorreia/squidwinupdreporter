<?php
require_once("../htdocs/squidLog.php");

class squidLogTest extends PHPUnit_Framework_TestCase {
    private static function getTempLogFileName() {
        $tempDir = sys_get_temp_dir();
        return tempnam($tempDir, "phpunit");
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
            )
        );
    }

    /**
     * @dataProvider logLineProvider
     */
    public function testIsWindowsUpdateLogLine($logLine, $isWindowsUpdate, $isHit) {
        $logFileName = self::getTempLogFileName();
        file_put_contents($logFileName, $logLine);

        $mySquidLog = new squidLog($logFileName);
        unlink($logFileName);

        $expectedEntryCount = ($isWindowsUpdate)?(1):(0);

        // we don't care whether it was a hit or miss for this test
        $entryCount  = count($mySquidLog->getHitEntries());
        $entryCount += count($mySquidLog->getMissEntries());

        $this->assertEquals($expectedEntryCount, $entryCount);
    }
}

?>
