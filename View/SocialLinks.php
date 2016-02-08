<?php

namespace Lightning\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;

class SocialLinks {
    public static function render($url) {
        JS::startup('lightning.social.initShare()');
        JS::set('social.twitter.url', Configuration::get('social.twitter.url'));
        $output = '<div class="social-share" data-url="' . Scrub::toURL($url) . '">';
        $output .= '<div class="share facebook"><i class="fa fa-facebook"></i> Share</a></div>';
        $output .= '<div class="share google"><i class="fa fa-google-plus"></i> Share</a></div>';
        $output .= '<div class="share twitter"><i class="fa fa-twitter"></i> Tweet</a></div>';
        $output .= '<div class="share email"><a href="mailto:?body=' . Scrub::toURL($url) . '"><i class="fa fa-send"></i> Email</a></div>';
        $output .= '</div>';
        return $output;
    }
}
