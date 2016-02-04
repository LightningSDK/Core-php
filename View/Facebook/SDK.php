<?php

namespace Lightning\View\Facebook;

use Lightning\View\JS;

class SDK {

    protected static $inited = false;

    public static function init() {
        if (self::$inited) {
            return '';
        }
        self::$inited = true;

        JS::add('//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.5&appId=247438262089646');

        return '<div id="fb-root"></div>';
    }
}
