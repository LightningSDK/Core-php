<?
/**
 * @file
 * Contains Lightning\Tools\Recaptcha
 */

namespace Lightning\Tools;

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
        require_once HOME_PATH . '/Lightning/Vendor/recaptcha/recaptcha-plugins/php/recaptchalib.php';
        return recaptcha_get_html(Configuration::get('recaptcha.public'));
    }

    /**
     * Validate the CAPTCHA input.
     *
     * @return boolean
     *   Whether the captcha was verified.
     */
    public static function verify() {
        require_once HOME_PATH . '/Lightning/Vendor/recaptcha/recaptcha-plugins/php/recaptchalib.php';
        $resp = recaptcha_check_answer (Configuration::get('recaptcha.private'),
            $_SERVER["REMOTE_ADDR"],
            $_POST["recaptcha_challenge_field"],
            $_POST["recaptcha_response_field"]);

        return !empty($resp->is_valid);
    }
}
