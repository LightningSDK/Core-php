<?php

namespace Lightning\Tools;

class Navigation {
    public static function redirect($url) {
        Output::sendCookies();
        header('Location: ' . $url);
        exit;
    }
}
