<?php

namespace Lightning\Tools;

class Navigation {
    public static function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}