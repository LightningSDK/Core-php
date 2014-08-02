<?php

namespace Lightning\Tools;

/**
 * Class Router
 * @package Lightning\Tools
 *
 * A helper to determine which handler to use for a requested URL.
 */
class Router extends Singleton {
    /**
     * A list of the static and dynamic routes.
     *
     * @var array
     */
    protected static $routes = array();

    /**
     * Load the configuration.
     */
    public function __construct() {
        self::$routes = Configuration::get('routes');
    }

    /**
     * Get the page handler for the current URL.
     *
     * @param string $url
     *   The current URL requested.
     *
     * @return string
     *   The namespace of the URL handler.
     */
    public function getRoute($url) {
        $url = rtrim($url, '/');
        if (isset(self::$routes['static'][$url])) {
            return self::$routes['static'][$url];
        }
        foreach (self::$routes['dynamic'] as $expr => $route) {
            if (preg_match('|' . $expr . '|', $url)) {
                return $route;
            }
        }
    }
}
