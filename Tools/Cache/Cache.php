<?php

namespace lightningsdk\core\Tools\Cache;

use lightningsdk\core\Tools\Configuration;

/**
 * Class Cache
 * @package lightningsdk\core\Tools\Cache
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
    const FILE = -1;
    const PHP_FILE = -2;

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
     * @return CacheControllerInterface
     *   An instance of the cache controller
     */
    protected static function getInstance($ttl = self::DEFAULT, $size = self::MEDIUM) {
        switch ($ttl) {
            case self::DEFAULT:
                if ($class = Configuration::get('cache.default.handler')) {
                    return new $class();
                }
                break;
            case self::PERMANENT:
                if ($class = Configuration::get('cache.permanent.handler')) {
                    return new $class();
                }
                // fallthrough
            case self::FILE:
                return new FileCache();
            case self::PHP_FILE:
                return new PHPFileCache();
            default:
                return new StaticCache();
        }
    }

    public static function item($key, $settings, $function) {
        $cache = static::getInstance($settings['ttl']);

        if ($val = $cache->get($key)) {
            return $val;
        }

        $val = $function();

        $cache->set($key, $val);
        return $val;
    }
}
