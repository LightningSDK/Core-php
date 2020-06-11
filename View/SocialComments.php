<?php

namespace lightningsdk\core\View;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Scrub;

class SocialComments {
    public static function render() {
        return \lightningsdk\core\View\Facebook\Comments::render();
    }
}
