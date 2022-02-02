<?php
namespace me\components;
use Me;
use Exception;
class Component {
    /**
     * 
     */
    public function __construct($config = []) {
        if (!empty($config)) {
            Me::configure($this, $config);
        }
        $this->init();
    }
    /**
     * 
     */
    public function init() {
        
    }
    /**
     * 
     */
    public function __get($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        elseif (method_exists($this, 'set' . $name)) {
            throw new Exception('Getting write-only property: ' . get_class($this) . '::' . $name);
        }
        else {
            throw new Exception('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }
    /**
     * 
     */
    public function __set($name, $value) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        }
        elseif (method_exists($this, 'get' . $name)) {
            throw new Exception('Setting read-only property: ' . get_class($this) . '::' . $name);
        }
        else {
            throw new Exception('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }
}