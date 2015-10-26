<?php

namespace Lightning\View;

use Lightning\Tools\SocialDrivers\Facebook;
use Lightning\Tools\SocialDrivers\Google;
use Lightning\Tools\SocialDrivers\Twitter;

class SocialLinks {
    public static function render() {
        $output = '';
        $output .= Facebook::renderLinks();
        $output .= Twitter::renderLinks();
        $output .= Google::renderLinks();
        return $output;
    }
}
