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
    protected static $routes = [];

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
    public static function parseRoute($url, $cli) {
        if (empty(self::$routes)) {
            self::$routes = Configuration::get('routes');
        }

        // If we are in CLI mode, and there is a command for cli only.
        if ($cli) {
            return isset(self::$routes['cli_only'][$url])
                ? self::$routes['cli_only'][$url]
                : null;
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

    public static function getRoute() {
        global $argv;
        if (Request::isCLI()) {
            // Handle a command line request.
            return static::parseRoute($argv[1], true);
        } else {
            // Handle a web page request.
            $handler = static::parseRoute(Request::getLocation(), false);
            if (is_array($handler) && !empty($handler['redirect'])) {
                Navigation::redirect($handler['redirect']);
            } else {
                return $handler;
            }
        }
    }
}
