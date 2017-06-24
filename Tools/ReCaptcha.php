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

    protected static $loaded = false;
    protected static $loadedType;

    protected static function loadJS($invisible = false) {
        if (static::$loaded && static::$loadedType !== $invisible) {
            throw new \Exception('Captcha type error.');
        }
        if ($invisible) {
            JS::set('invisibleRecaptcha.publicKey', Configuration::get('recaptcha.invisible.public'));
            JS::startup('lightning.forms.initInvisibleCaptcha()', ['https://www.google.com/recaptcha/api.js?render=explicit']);
        } else {
            JS::add('https://www.google.com/recaptcha/api.js');
        }
    }

    /**
     * Render a ReCaptcha input.
     *
     * @return string
     *   Rendered HTML.
     */
    public static function render() {
        static::loadJS();
        return '<div class="captcha_container clearfix">
                <div class="g-recaptcha" data-sitekey="' . Configuration::get('recaptcha.public') . '"></div>
                <input type="text" name="captcha_abide" id="captcha_abide" required>
                <small class="error">Please check the box.</small></div>';
    }

    public static function renderInvisible($text = 'Submit', $class = '', $callback = '') {
        static::loadJS(true);
        return '<input type="hidden" name="recaptcha-type" value="invisible"/><button class="g-recaptcha invisible-recaptcha ' . $class . '">' . $text . '</button>';
    }

    /**
     * Validate the CAPTCHA input.
     *
     * @return boolean
     *   Whether the captcha was verified.
     */
    public static function verify() {
        $client = new RestClient('https://www.google.com/recaptcha/api/siteverify');
        $secret = Request::get('recaptcha-type') === 'invisible' ? Configuration::get('recaptcha.invisible.private') : Configuration::get('recaptcha.private');
        $client->set('secret', $secret);
        $client->set('response', Request::post('g-recaptcha-response'));
        $client->set('remoteip', Request::server(Request::IP));
        $client->callPost();

        return (boolean) $client->get('success');
    }
}
