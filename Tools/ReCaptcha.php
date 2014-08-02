<?

namespace Lightning\Tools;

class ReCaptcha {
    public static function render() {
        require_once HOME_PATH . '/Lightning/vendor/recaptchalib.php';
        return recaptcha_get_html(Configuration::get('captcha.public'));
    }

    public static function verify() {
        require_once HOME_PATH . '/Lightning/vendor/recaptchalib.php';
        $resp = recaptcha_check_answer (Configuration::get('captcha.private'),
            $_SERVER["REMOTE_ADDR"],
            $_POST["recaptcha_challenge_field"],
            $_POST["recaptcha_response_field"]);

        return !empty($resp->is_valid);
    }
}
