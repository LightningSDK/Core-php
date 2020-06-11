<?php

namespace lightningsdk\core\View\SocialMedia;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\View\JS;

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
                        $output .= \lightningsdk\core\Tools\SocialDrivers\Facebook::renderFollow();;
                        break;
                    case 'google':
                        $output .= \lightningsdk\core\Tools\SocialDrivers\Google::renderFollow();;
                        break;
                    case 'twitter':
                        $output .= \lightningsdk\core\Tools\SocialDrivers\Twitter::renderFollow();
                        break;
                    case 'linkedin':
                        $output .= \lightningsdk\core\Tools\SocialDrivers\LinkedIn::renderFollow();
                        break;
                    case 'youtube':
                        $output .= \lightningsdk\core\Tools\SocialDrivers\YouTube::renderFollow();
                        break;
                    case 'instagram':
                        $output .= \lightningsdk\core\Tools\SocialDrivers\Instagram::renderFollow();
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
