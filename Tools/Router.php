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
     * @param boolean $cli
     *   Whether the request was made from the command line.
     *
     * @return string
     *   The namespace of the URL handler.
     */
    public function getRoute($url, $cli) {
        $url = rtrim($url, '/');
        // If we are in CLI mode, and there is a command for cli only.
        if ($cli && isset(self::$routes['cli_only'][$url])) {
            return self::$routes['cli_only'][$url];
        }
        // If this is listed in the static url list.
        if (isset(self::$routes['static'][$url])) {
            return self::$routes['static'][$url];
        }
        // If this matches one of the regex urls.
        if (!empty(self::$routes['dynamic'])) {
            foreach (self::$routes['dynamic'] as $expr => $route) {
                if (preg_match('|' . $expr . '|', $url)) {
                    return $route;
                }
            }
        }
    }
}
