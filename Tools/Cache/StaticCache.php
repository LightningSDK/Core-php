<?php

namespace lightningsdk\core\Tools\Cache;

class StaticCache extends CacheController implements CacheControllerInterface {

    protected static $cache = [];

    public function get($key, $default = null) {
        if (array_key_exists($key, static::$cache)) {
            return static::$cache[$key];
        }
        return $default;
    }

    public function set($key, $value) {
        static::$cache[$key] = $value;
    }

    public function unset($key) {
        unset(static::$cache[$key]);
    }
}
