<?php

namespace Lightning\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;

class SocialLinks {
    public static function render($url) {
        JS::startup('lightning.social.initShare()');
        JS::set('social.twitter.url', Configuration::get('social.twitter.url'));
        $output = '<div class="social-share" data-url="' . Scrub::toURL($url) . '">';
        $output .= '<div class="share facebook"><i class="fa fa-facebook"></i> Share</div>';
        $output .= '<div class="share google"><i class="fa fa-google-plus"></i> Share</div>';
        $output .= '<div class="share twitter"><i class="fa fa-twitter"></i> Tweet</div>';
        $output .= '<div class="share linkedin"><i class="fa fa-linkedin"></i> Linked In</div>';
        $output .= '<div class="share email"><a href="mailto:?body=' . rawurlencode('I thought you might find this page interesting:') . '%0D%0A%0D%0A' . Scrub::toURL($url) . '" title="Email"><i class="fa fa-send"></i> Email</a></div>';
        $output .= '</div>';
        return $output;
    }
}
