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

    /**
     * @dataProvider badPropertyProvider
     */
    public function testSetBadProperty($property) {
        $mySquidLogEntry = new squidLogEntry(array("client" => "test"));

        $this->setExpectedException("RuntimeException");
        $mySquidLogEntry->$property = "test";
    }

    /**
     * @dataProvider goodPropertyProvider
     */
    public function testSetGoodProperty($property) {
        $mySquidLogEntry = new squidLogEntry(array("client" => "test"));

        $mySquidLogEntry->$property = "test2";
        $this->assertEquals($mySquidLogEntry->$property, "test2");
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

    public function testIsHitForHit() {
        $mySquidLogEntry = new squidLogEntry(array("hier" => "NONE"));
        $this->assertTrue($mySquidLogEntry->isHit());
    }

    public function testIsHitforMiss() {
        $mySquidLogEntry = new squidLogEntry(array("hier" => "DIRECT"));
        $this->assertFalse($mySquidLogEntry->isHit());
    }
}

?>
