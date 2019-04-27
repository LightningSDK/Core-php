<?php

namespace Lightning\View\SocialMedia;

use Lightning\Tools\Configuration;
use Lightning\View\JS;

class Follow {
    public static function render() {
        JS::startup('lightning.social.initShare()');
        JS::set('social.twitter.url', Configuration::get('social.twitter.url'));
        $options = Configuration::get('social.follow');
        $output = '<div class="social-follow">';
        if (!empty($options)) {
            foreach ($options as $option => $enabled) {
                if (!$enabled) {
                    continue;
                }
                switch ($option) {
                    case 'facebook':
                        $output .= \Lightning\Tools\SocialDrivers\Facebook::renderFollow();;
                        break;
                    case 'google':
                        $output .= \Lightning\Tools\SocialDrivers\Google::renderFollow();;
                        break;
                    case 'twitter':
                        $output .= \Lightning\Tools\SocialDrivers\Twitter::renderFollow();
                        break;
                    case 'linkedin':
                        $output .= \Lightning\Tools\SocialDrivers\LinkedIn::renderFollow();
                        break;
                    case 'youtube':
                        $output .= \Lightning\Tools\SocialDrivers\YouTube::renderFollow();
                        break;
                    case 'instagram':
                        $output .= \Lightning\Tools\SocialDrivers\Instagram::renderFollow();
                        break;
                }
            }
        }

        $output .= '</div>';
        return $output;
    }

    /**
     * Render the social media share buttons with {{social-share url=""}}
     * @param $options
     * @return string
     */
    public static function renderMarkup($options) {
        return self::render();
    }
}
