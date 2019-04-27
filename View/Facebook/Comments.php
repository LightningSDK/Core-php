<?php

namespace Lightning\View\Facebook;

class Comments {
    /**
     * @param bool $url
     * @return string
     *
     * @deprecated
     */
    public static function render($url = false) {
        return SDK::init() . '<div class="fb-comments" data-href="' . \Lightning\Tools\Request::getURL() .'" data-width="100%" data-numposts="5"></div>';
    }
}
