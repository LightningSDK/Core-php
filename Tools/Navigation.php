<?php

namespace Lightning\Tools;

class Navigation {
    public static function redirect($url = null, $query = array()) {
        if (empty($url)) {
            $url = $_SERVER['REQUEST_URI'];
        }
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        Output::sendCookies();
        header('Location: ' . $url);
        exit;
    }

    public static function currentLocation() {
        return $_SERVER['REQUEST_URI'];
    }
}
