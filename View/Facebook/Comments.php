<?php

namespace Lightning\View\Facebook;

use Lightning\Tools\Configuration;
use Lightning\Tools\Request;

class Comments {
    public static function render($url = false) {
        return SDK::init() . '<div class="fb-comments" data-href="' . ($url ?: Configuration::get('web_root') . '/' . Request::getLocation()) . '" data-numposts="5"></div>';
    }
}
