<?php
require_once("../htdocs/squidLogEntry.php");

class squidLogEntryTest extends PHPUnit_Framework_TestCase {
    public function badPropertyProvider() {
        return array (
            array("test"),
            array("hier1"),
            array("urll"),
            array("request")
        );
    }

    public function goodPropertyProvider() {
        return array (
            array("timestamp"),
            array("elapsed"),
            array("client"),
            array("action"),
            array("code"),
            array("size"),
            array("method"),
            array("url"),
            array("hier"),
            array("from"),
            array("content")
        );
    }


    /**
     * @dataProvider badPropertyProvider
     * @expectedException RuntimeException
     */
    public function testConstructBadProperties($property) {
        $mySquidLogEntry = new squidLogEntry(array($property => "test"));
    }

    /**
     * @dataProvider goodPropertyProvider
     */
    public function testConstructGoodProperties($property) {
         $mySquidLogEntry = new squidLogEntry(array($property => "test"));
         $this->assertEquals("test", $mySquidLogEntry->$property);
    }

    public function testSetProperty() {
        $mySquidLogEntry = new squidLogEntry(array("client" => "test"));

        $this->setExpectedException("RuntimeException");
        $mySquidLogEntry->from = "test";
    }

    /**
     * @dataProvider badPropertyProvider
     */
    public function testGetBadProperties($property) {
        $mySquidLogEntry = new squidLogEntry(array("client" => "test"));

        $this->setExpectedException("RuntimeException");
        $mySquidLogEntry->$property;
    }

    /**
     * @dataProvider goodPropertyProvider
     */
    public function testGetGoodProperties($property) {
        $mySquidLogEntry = new squidLogEntry(array("client" => ""));
        $this->assertEquals("", $mySquidLogEntry->$property);
    }

}

?>
