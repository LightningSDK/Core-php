<?php

namespace Lightning\Tools\SocialDrivers;

use Lightning\Tools\Configuration;

class Instagram {
    public static function renderFollow() {
        if ($youtube_page = Configuration::get('social.instagram.url')) {
            JS::add('https://apis.google.com/js/platform.js');
            return '<div class="g-ytsubscribe" data-channelid="' . $youtube_page . '" data-layout="default" data-count="default"></div>';
        }
        return '';
    }
}
