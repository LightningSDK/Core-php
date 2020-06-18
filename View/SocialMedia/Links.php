<?php

namespace lightningsdk\core\View\SocialMedia;

use lightningsdk\core\Model\URL;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\Tools\Session\BrowserSession;
use lightningsdk\core\View\JS;

class Links {
    public static function render($url = '') {
        if (empty($url)) {
            $url = Request::getURL();
        }
        $options = Configuration::get('social');
        $output = '<div class="social-links">';
        if (!empty($options)) {
            foreach ($options as $option => $settings) {
                if (empty($settings['url'])) {
                    continue;
                }
                switch ($option) {
                    case 'facebook':
                        $output .= '<li><a class="btn btn-default" title="Facebook" href="' . $settings['url'] . '" target="_blank"><i class="fa fa-facebook"></i></a></li>';
                        break;
                    case 'youtube':
                        $output .= '<li><a class="btn btn-default" title="YouTube" href="https://youtube.com/channel' . $settings['url'] . '" target="_blank"><i class="fa fa-youtube"></i></a></li>';
                        break;
                    case 'twitter':
                        $output .= '<li><a class="btn btn-default" title="Twitter" href="https://twitter.com/' . $settings['url'] . '" target="_blank"><i class="fa fa-twitter"></i></a></li>';
                        break;
                    case 'instagram':
                        $output .= '<li><a class="btn btn-default" title="Instagram" href="' . $settings['url'] . '" target="_blank"><i class="fa fa-pinterest"></i></a></li>';
                        break;
                    case 'pinterest':
                        $output .= '<li><a class="btn btn-default" title="Pinterest" href="' . $settings['url'] . '" target="_blank"><i class="fa fa-pinterest"></i></a></li>';
                        break;
                    case 'linkedin':
                        $output .= '<li><a class="btn btn-default" title="Linked In" href="' . $settings['url'] . '" target="_blank"><i class="fa fa-linkedin"></i></a></li>';
                        break;
                }
            }
        }
        $output .= "<li><a href='mailto:?body=" . rawurlencode('I thought you might find this page interesting:') . " %0D%0A%0D%0A" . Scrub::toURL($url) . "' title='Email'><i class='fa fa-send'></i></a></li>";
        $output .= "</div>";
        return $output;
    }

    /**
     * Render the social media share buttons with {{social-share url=""}}
     * @param $options
     * @return string
     */
    public static function renderMarkup($options) {
        $url = !empty($options['url']) ? URL::getAbsolute($options['url']) : Request::getURL();

        if (!empty($options['add-ref']) && $referrer_id = BrowserSession::getInstance()->user_id) {
            $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'ref=' . $referrer_id;
        }

        return self::render($url);
    }
}
