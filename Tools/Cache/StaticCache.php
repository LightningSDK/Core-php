<?php

namespace Lightning\Tools\Cache;

class StaticCache extends CacheController {

    protected static $cache = [];

    public function __construct($settings = array()) {

    }

    public function isValid() {
        return isset(self::$cache[$this->name]);
    }

    public function load($name, $default = null) {
        $this->setName($name);
        $this->value = &self::$cache[$name];
    }
}
