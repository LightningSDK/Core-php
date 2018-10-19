<?php

namespace Lightning\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;

class SocialComments {
    public static function render() {
        return \Lightning\View\Facebook\Comments::render();
    }
}
