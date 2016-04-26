<?php

namespace Lightning\Tools;

class Navigation {
    /**
     * Redirect to another location.
     *
     * @param string $url
     *   The page to redirect to. If left null, it will redirect to the current page.
     * @param array $query
     *   Additional query parameters to add.
     * @param boolean $permanent
     *   If set to true, this will be a 301 permanent redirect.
     */
    public static function redirect($url = null, $query = [], $permanent = false) {
        if (empty($url)) {
            $url = '/' . Request::getLocation();
        }
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        Messenger::storeInSession();
        Output::sendCookies();
        if ($permanent) {
            http_response_code(301);
        }
        header('Location: ' . $url);
        exit;
    }

    /**
     * Get the current URL.
     *
     * @return string
     *   The location of the current page.
     */
    public static function currentLocation() {
        return $_SERVER['REQUEST_URI'];
    }
}
