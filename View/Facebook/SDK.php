<?php

namespace Lightning\View\Facebook;

use Lightning\Tools\Configuration;
use Lightning\View\JS;

class SDK {

    protected static $inited = false;

    public static function init() {
        if (self::$inited) {
            return '';
        }
        self::$inited = true;

        $appid = Configuration::get('social.facebook.appid');
        JS::add('//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.6&appId=' . $appid);
        JS::set('social.facebook.appid', $appid);
        JS::set('social.facebook.scope', Configuration::get('social.facebook.scope'));

        return '<div id="fb-root"></div>';
    }
}
