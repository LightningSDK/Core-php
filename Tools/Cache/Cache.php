<?php

namespace Lightning\Tools\Cache;

use Lightning\Tools\Configuration;

class Cache {

    const TEMPORARY = 1;
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const MONTH = 2592000;
    const PERMANENT = INF;

    const SMALL = 1;
    const MEDIUM = 2;
    const LARGE = 3;

    public static function get($name, $ttl = self::TEMPORARY, $size = self::MEDIUM) {
        $cache = self::getType($ttl, $size);
        $cache->load($name);
        return $cache;
    }

    protected static function getType($ttl = self::TEMPORARY, $size = self::MEDIUM) {
        if ($ttl == self::TEMPORARY) {
            // Static Cache
            return new StaticCache();
        } elseif ($ttl == self::PERMANENT) {
            if ($class = Configuration::get('cache.permanent.handler')) {
                return new $class();
            } else {
                return new FileCache();
            }
        }
    }

}
