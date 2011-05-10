<?php

class squidLogEntry {
    private $properties = array (
        "timestamp" => "",
        "elapsed"   => "",
        "client"    => "",
        "action"    => "",
        "code"      => "",
        "size"      => "",
        "method"    => "",
        "url"       => "",
        "hier"      => "",
        "from"      => "",
        "content"   => "",
    );


    public function __construct($propertyArray) {
        foreach($propertyArray as $name => $value) {
            if($this->isValidProperty($name)) {
                $this->properties[$name] = $value;
            } else {
                throw(new RuntimeException("Attempting to initialize an unknown property of " . __CLASS__ . ": $name"));
            }
        }
    }

    public function __set($name, $value) {
        if($this->isValidProperty($name)) {
            $this->properties[$name] = $value;
        } else {
            throw(new RuntimeException("Attempting to modify an unknown property of " . __CLASS__ . ": $name"));
        }
    }

    public function __get($name) {
        if($this->isValidProperty($name)) {
            return $this->properties[$name];
        } else {
            throw(new RuntimeException("Attempting to retrieve an unknown property of " . __CLASS__ . ": $name"));
        }
    }

    private function isValidProperty($property) {
        if(array_key_exists($property, $this->properties)) {
            return true;
        } else {
            return false;
        }
    }

    public function isHit() {
        if($this->properties["hier"] == "NONE") {
            return true;
        } else {
            return false;
        }
    }
}

?>
