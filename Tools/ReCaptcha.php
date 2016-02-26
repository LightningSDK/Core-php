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
        echo '<div class="captcha_container clearfix">
                <div class="g-recaptcha" data-sitekey="' . Configuration::get('recaptcha.public') . '"></div>
                <input type="text" name="captcha_abide" id="captcha_abide" required>
                <small class="error">Please check the box.</small>
        </div>';
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
