<?php

namespace Lightning\Tools\Cache;

interface CacheControllerInterface {
    public function get($key, $default = null);
    public function set($key, $value);
}
