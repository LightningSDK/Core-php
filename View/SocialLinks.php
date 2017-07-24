<?php

namespace Lightning\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;

class SocialLinks {
    public static function render($url) {
        JS::startup('lightning.social.initShare()');
        JS::set('social.twitter.url', Configuration::get('social.twitter.url'));
        $options = Configuration::get('social.share');
        $output = '<div class="social-share" data-url="' . Scrub::toURL($url) . '">';
        foreach ($options as $option => $enabled) {
            if (!$enabled) {
                continue;
            }
            switch ($option) {
                case 'facebook':
                    $output .= '<div class="share facebook"><i class="fa fa-facebook"></i> Share</div>';
                    break;
                case 'google':
                    $output .= '<div class="share google"><i class="fa fa-google-plus"></i> Share</div>';
                    break;
                case 'twitter':
                    $output .= '<div class="share twitter"><i class="fa fa-twitter"></i> Tweet</div>';
                    break;
                case 'pinterest':
                    $output .= '<div class="share pinterest"><i class="fa fa-pinterest"></i> Pin</div>';
                    break;
                case 'linkedin':
                    $output .= '<div class="share linkedin"><i class="fa fa-linkedin"></i> Linked In</div>';
                    break;
                case 'email':
                    $output .= '<div class="share email"><a href="mailto:?body=' . rawurlencode('I thought you might find this page interesting:') . '%0D%0A%0D%0A' . Scrub::toURL($url) . '" title="Email"><i class="fa fa-send"></i> Email</a></div>';
                    break;
            }
        }
        $output .= '</div>';
        return $output;
    }
}
