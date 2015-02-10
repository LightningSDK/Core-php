<?php
/**
 * @file
 * Contains Lightning\Tools\Scrub
 */

namespace Lightning\Tools;

/**
 * A helper for data sanitization.
 *
 * @package Lightning\Tools
 */
class Scrub {

    /**
     * Basic html elements allowed in HTML fields by users.
     */
    const SCRUB_BASIC_HTML = 'p,b,a[href|name|target|title],i,strong,em,img[src|width|height],table[cellpadding|cellspacing|border],tr,td,tbody,hr,h1,h2,h3,h4,h5,h6,*[id|name|align|style|alt|class],sup,sub,ul,ol,li,span,font[color|size],div,br,blockquote,code,pre';
    /**
     * Advanced html elements allowed in HTML primarily by admins.
     *
     * @todo enable align|allowfullscreen for iframe
     */
    const SCRUB_ADVANCED_HTML = 'input[type|value|checked|src],select,option[value],form[target|action|method],textarea,iframe[frameborder|src|height|width]';

    /**
     * Allowed CSS rules.
     */
    const SCRUB_BASIC_CSS = 'height,width,color,background-color,vertical-align,text-align,margin,margin-left,margin-right,margin-top,margin-bottom,padding,padding-left,margin-right,margin-top,margin-bottom,border,border-left,border-right,border-top,border-bottom,float,font-size';

    /**
     * Convert text to HTML safe output.
     *
     * @param string $code
     *   The original text.
     *
     * @return string
     *   The HTML safe output.
     */
    public static function toHTML($code) {
        return htmlspecialchars($code, ENT_QUOTES);
    }

    /**
     * Encode a string as a query string parameter.
     *
     * @param string $value
     *   The value.
     *
     * @return string
     *   The URL encoded value.
     */
    public static function toURL($value) {
        return urlencode($value);
    }

    /**
     * Convert a string to a url safe string.
     *
     * @param string $value
     *   A string.
     *
     * @return string
     *   The url safe string.
     */
    public static function url($value) {
        $url = preg_replace("/(&[#a-z0-9]+;)/i", "_", $value);
        $url = preg_replace("/[^a-z0-9]/i", "_", $url);
        return $url;
    }

    /**
     * Validate an email address.
     *
     * @param string $email
     *   The email address.
     *
     * @return bool|string
     *   A valid email address if found or false.
     */
    public static function email($email) {
        $email = str_replace(" ", '', strtolower($email));
        if (!preg_match('/^[_a-zA-Z0-9-]+([_\.\-+][a-zA-Z0-9]+)*@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/',$email))
            return false;
        else
            return $email;
    }

    /**
     * Validate a hex string.
     *
     * @param string $code
     *   The source text.
     *
     * @return string|boolean
     *   The valid hex string or false if this is not a valid hex string.
     */
    public static function hex($code) {
        $code = str_replace(' ', '', $code);
        if (preg_match("/^[a-z0-9\.]+$/i", $code)) {
            return $code;
        }
        return false;
    }

    /**
     * Validate a base64 encoded string.
     *
     * @param string $string
     *   The value to test.
     *
     * @return string|boolean
     *   The validated value or false.
     */
    public static function base64($string) {
        $string = str_replace(' ', '', $string);
        if (preg_match("|^[a-z0-9+/]+={0,2}$|i", $string)) {
            return $string;
        }
        return false;
    }

    /**
     * Validate a base64 encrypted string.
     *
     * This is the same as base64 except it includes a parenthesis
     * do delimit the iv and the cyphertext.
     *
     * @param string $string
     *   The value to test.
     *
     * @return string|boolean
     *   The validated value or false.
     */
    public static function encrypted($string) {
        $string = str_replace(' ', '', $string);
        if (preg_match("|^[a-z0-9+/]+={0,2}:[a-z0-9+/]+={0,2}$|i", $string)) {
            return $string;
        }
        return false;
    }

    /**
     * Validate text, removing all HTML.
     *
     * @param string $text
     *   The source string.
     *
     * @return string
     *   The validated text without any HTML.
     */
    public static function text($text) {
        $purifier = HTMLPurifierWrapper::getInstance();
        $config = HTMLPurifierConfig::createDefault();

        $config->set('HTML.Allowed', '');
        $config->set('CSS.AllowedProperties','');
        $config->set('Core.EscapeNonASCIICharacters',true);

        return $purifier->purify($text, $config);
    }

    /**
     * Validate HTML.
     *
     * @param string $html
     *   The source HTML.
     * @param string $allowed_tags
     *   The allowed HTML tags.
     * @param string $allowed_css
     *   The allowed css elements.
     * @param boolean $trusted
     *   Whether this is from a trusted source like an admin user.
     * @param boolean $full_page
     *   Whether to allow all HTML.
     *   TODO: This currently skips all validation and returns the input.
     *
     * @return string
     *   The sanitized HTML.
     */
    public static function html($html, $allowed_tags = '', $allowed_css = '', $trusted = false, $full_page = false) {
        $purifier = HTMLPurifierWrapper::getInstance();
        $config = HTMLPurifierConfig::createDefault();

        if ($full_page) {
            return $html;
        } elseif ($trusted) {
            $config->set('CSS.Trusted', true);
            $config->set('HTML.Trusted', true);
            $config->set('Attr.EnableID', true);
            $allowed_tags = self::SCRUB_BASIC_HTML . ',' . self::SCRUB_ADVANCED_HTML;
        } else {
            $config->set('CSS.Trusted', false);
            $config->set('HTML.Trusted', false);
            $config->set('Attr.EnableID', false);
            if (!empty($allowed_tags) && $allowed_tags == '.') {
                $allowed_tags = self::SCRUB_BASIC_HTML . ',' . substr($allowed_tags, 1);
            } else {
                $allowed_tags = self::SCRUB_BASIC_HTML;
            }
        }

        if (empty($allowed_css) || $allowed_css[0] == '.') {
            $allowed_css = self::SCRUB_BASIC_CSS . ',' . substr($allowed_css, 1);
        }
        elseif ($allowed_css == '') {
            $allowed_css = self::SCRUB_BASIC_CSS;
        }

        $config->set('HTML.Allowed', $allowed_tags);
        $config->set('CSS.AllowedProperties', $allowed_css);
        $config->set('Core.EscapeNonASCIICharacters', true);

        return $purifier->purify( $html, $config );
    }

    /**
     * Check if a value is a boolean or representation thereof.
     *
     * @param mixed $val
     *   The value to check.
     *
     * @return boolean
     *   Whether the value equates to true.
     */
    public static function boolean($val) {
        return (intval($val) > 0 || $val === true || strtolower($val) === 'true');
    }

    public static function int($val) {
        return intval(str_replace(',', '', $val));
    }

    public static function float($val) {
        return floatval(str_replace(',', '', $val));
    }
}
