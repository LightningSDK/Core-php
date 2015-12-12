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
     */
    public static function redirect($url = null, $query = []) {
        if (empty($url)) {
            $url = '/' . Request::getLocation();
        }
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        Messenger::storeInSession();
        Output::sendCookies();
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
