<?php

namespace Lightning\Tools;

class Navigation {
    public static function redirect($url = null) {
        if (empty($url)) {
            $url = $_SERVER['REQUEST_URI'];
        }
        Output::sendCookies();
        header('Location: ' . $url);
        exit;
    }
}
