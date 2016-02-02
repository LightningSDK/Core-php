<?php
/**
 * @file
 * Contains Lightning\Tools\Recaptcha
 */

namespace Lightning\Tools;
use Lightning\Tools\Communicator\RestClient;
use Lightning\View\JS;

/**
 * A class for rendering the Recaptcha verification elements.
 *
 * @package Lightning\Tools
 */
class ReCaptcha {
    /**
     * Render a ReCaptcha input.
     *
     * @return string
     *   Rendered HTML.
     */
    public static function render() {
        JS::add('https://www.google.com/recaptcha/api.js');
        echo '<div class="g-recaptcha" data-sitekey="' . Configuration::get('recaptcha.public') . '"></div>';
    }

    /**
     * Validate the CAPTCHA input.
     *
     * @return boolean
     *   Whether the captcha was verified.
     */
    public static function verify() {
        $client = new RestClient('https://www.google.com/recaptcha/api/siteverify');
        $client->set('secret', Configuration::get('recaptcha.private'));
        $client->set('response', Request::post('g-recaptcha-response'));
        $client->set('remoteip', Request::server(Request::IP));
        $client->callPost();

        return (boolean) $client->get('success');
    }
}
