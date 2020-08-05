<?php

namespace lightningsdk\core\Tools\SocialDrivers;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\View\JS;

class YouTube {
    public static function renderFollow() {
        if ($youtube_page = Configuration::get('social.youtube.url')) {
            return '<script src="https://apis.google.com/js/platform.js"></script><div class="g-ytsubscribe" data-channelid="' . $youtube_page . '" data-layout="default" data-count="default"></div>';
        }
        return '';
    }
}
