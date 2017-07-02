<?php

namespace Lightning\Tools\Cache;

use Lightning\Tools\Configuration;

/**
 * Class Cache
 * @package Lightning\Tools\Cache
 *
 * This is a main class controller that will load more specific caches depending on the
 * cache requirements such as TTL and data size.
 */
class Cache {

    const DEFAULT = 0;
    const TEMPORARY = 1;
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const MONTH = 2592000;
    const PERMANENT = INF;

    const SMALL = 1;
    const MEDIUM = 2;
    const LARGE = 3;

    /**
     * Get the cache object pointing at a specific entry.
     *
     * @param int $ttl
     * @param int $size
     *
     * @return CacheController
     *   The cache controller.
     */
    public static function get($ttl = self::TEMPORARY, $size = self::MEDIUM) {
        $cache = self::getInstance($ttl, $size);
        return $cache;
    }

    /**
     * @param int $ttl
     *   The time the cache should be held for.
     * @param int $size
     *   The size class of the data to store.
     *
     * @return CacheController
     *   An instance of the cache controller
     */
    protected static function getInstance($ttl = self::DEFAULT, $size = self::MEDIUM) {
        if ($ttl == self::DEFAULT) {
            if ($class = Configuration::get('cache.default.handler')) {
                return new $class();
            }
        } elseif ($ttl == self::PERMANENT) {
            if ($class = Configuration::get('cache.permanent.handler')) {
                return new $class();
            } else {
                return new FileCache();
            }
        } else {
            // Default fallback.
            return new StaticCache();
        }
    }
}
