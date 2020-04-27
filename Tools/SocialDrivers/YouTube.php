<?php

namespace Lightning\Tools\SocialDrivers;

use Lightning\Tools\Configuration;
use Lightning\View\JS;

class YouTube {
    public static function renderFollow() {
        if ($youtube_page = Configuration::get('social.youtube.url')) {
            JS::add('https://apis.google.com/js/platform.js');
            return '<div class="g-ytsubscribe" data-channel="' . $youtube_page . '" data-layout="default" data-count="default"></div>';
        }
        return '';
    }
}
