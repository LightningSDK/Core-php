<?php
/**
 * @file
 * Contains lightningsdk\core\Tools\Scrub
 */

namespace lightningsdk\core\Tools;
use tidy;

/**
 * A helper for data sanitization.
 *
 * @package lightningsdk\core\Tools
 */
class Scrub {

    /**
     * Basic html elements allowed in HTML fields by users.
     */
    const SCRUB_BASIC_HTML = 'p,b,a[href|name|target|title],i,u,strong,small,em,img[src|width|height|style],table[cellpadding|cellspacing|border|width|height],tr[height],td[colspan|rowspan|width|height],tbody,thead,tfoot,hr,h1,h2,h3,h4,h5,h6,*[id|name|align|style|alt|class],sup,sub,ul,ol,li,span,font[color|size],div,br,blockquote,code,pre';
    /**
     * Advanced html elements allowed in HTML primarily by admins.
     *
     * @todo enable align|allowfullscreen for iframe
     */
    const SCRUB_ADVANCED_HTML = 'input[type|value|checked|src],select,option[value],form[target|action|method],textarea,iframe[frameborder|src|height|width],a[target],section[style],div[style]';

    /**
     * Allowed CSS rules.
     */
    const SCRUB_BASIC_CSS = 'height,width,min-height,min-width,max-height,max-width,color,background-color,background-size,background-image,vertical-align,text-align,margin,margin-left,margin-right,margin-top,margin-bottom,padding,padding-left,margin-right,margin-top,margin-bottom,border,border-left,border-right,border-top,border-bottom,float,font-size,display';

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
        $url = preg_replace('/[^a-z0-9-_ .]/i', '', $value);
        $url = preg_replace('/[^a-z0-9-_.]/i', '-', $url);
        $url = trim($url, '-_');
        $url = preg_replace('/-+/', '-', $url);
        return $url;
    }

    /**
     * Remove all characters that are not alphanumeric.
     *
     * @param string $value
     *   A string.
     *
     * @return string
     *   The adjusted string.
     */
    public static function compressAlphaNumeric($value){
        return preg_replace("/[^a-zA-Z0-9]+/", "", $value);
    }

    /**
     * Create an abbreviated number like 0.1, 3.5, 45, 12k, 14M, 'A LOT!'
     *
     * @param float $number
     *   The number to convert.
     * @param boolean $decimal
     *   Whether to include a single decimal for numbers under 10.
     *
     * @return string
     *   The abbreviated number.
     */
    public static function shortNumber($number, $decimal = false) {
        $number = floatval($number);
        if ($number < 10 && $decimal) {
            return number_format($number, 1);
        }
        elseif ($number < 1000) {
            return intval($number);
        }
        elseif ($number < 1000000) {
            return intval($number/1000) . 'k';
        }
        elseif ($number < 1000000000) {
            return intval($number/1000000) . 'M';
        }
        else {
            return 'A LOT!';
        }
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
        $email = trim($email);
        if (!preg_match('/^[_a-z0-9-]+([_\.\-+][a-z0-9]+)*@[a-z0-9\-]+\.[a-z0-9\-\.]+$/i',$email)) {
            return false;
        } else {
            return $email;
        }
    }

    /**
     * Validate a bitcoin address.
     *
     * @param string $bitcoin_address
     *
     * @return boolean|string
     */
    public static function bitcoinAddress($bitcoin_address) {
        $bitcoin_address = trim($bitcoin_address);
        if (preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $bitcoin_address)) {
            return $bitcoin_address;
        } else {
            return false;
        }
    }

    /**
     * Validate a hex string.
     *
     * @param string $code
     *   The source text.
     *
     * @return string|boolean
     *   The valid hex string or false if this is not a valid hex string.
     *
     * TODO: this should be a-f and should not include a dot.
     */
    public static function hex($code) {
        $code = str_replace(' ', '', $code);
        if (preg_match('/^[a-z0-9\.]+$/i', $code)) {
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
        if (preg_match('|^[a-z0-9+/_\-\.]+={0,2}$|i', $string)) {
            return $string;
        }
        return false;
    }

    /**
     * Validate a base64 encrypted string.
     *
     * This is the same as base64 except it includes a parenthesis
     * do delimit the iv and the cipher text.
     *
     * @param string $string
     *   The value to test.
     *
     * @return string|boolean
     *   The validated value or false.
     */
    public static function encrypted($string) {
        $string = str_replace(' ', '', $string);
        if (preg_match('|^[a-z0-9+/]+={0,2}:[a-z0-9+/]+={0,2}$|i', $string)) {
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
        return htmlentities(strip_tags($text));
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

        if ($full_page || $trusted) {
            return static::trustedHTML($html, $full_page);
        }

        $config->set('CSS.Trusted', false);
        $config->set('HTML.Trusted', false);
        $config->set('Attr.EnableID', false);

        if (!empty($allowed_tags) && $allowed_tags == '.') {
            $allowed_tags = self::SCRUB_BASIC_HTML . ',' . substr($allowed_tags, 1);
        } else {
            $allowed_tags = self::SCRUB_BASIC_HTML;
        }

        if (!empty($allowed_css) && $allowed_css[0] == '.') {
            $allowed_css = self::SCRUB_BASIC_CSS . ',' . substr($allowed_css, 1);
        } elseif (empty($allowed_css)) {
            $allowed_css = self::SCRUB_BASIC_CSS;
        }

        $config->set('HTML.Allowed', $allowed_tags);
        $config->set('CSS.AllowedProperties', $allowed_css);
        $config->set('Core.EscapeNonASCIICharacters', true);

        return $purifier->purify($html, $config);
    }

    /**
     * When HTML is trusted, this function just validates it, ensuring that all tags are closed.
     * @param string $html
     *   The full html.
     * @param boolean $full_page
     *   If set, the html and body tags will be ensured to be present.
     *
     * @return string
     *   Validated HTML.
     */
    public static function trustedHTML($html, $full_page = false) {
        $tidy = new tidy();
        $settings = [];
        if (!$full_page) {
            $settings['show-body-only'] = true;
        }
        return $tidy->repairString($html, $settings);
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

    public static function decimal($val) {
        $string = str_replace(' ', '', $val);
        if (preg_match('|^-?[0-9]*.?[0-9]*$|', $string)) {
            return $string;
        }
        return false;
    }

    public static function json($string) {
        return json_decode($string);
    }

    public static function objectToAssocArray($object) {
        return json_decode(json_encode($object), true);
    }

    public static function json_string($string) {
        if (null !== json_decode($string)) {
            return $string;
        } else {
            return null;
        }
    }

    public static function ipToHex($string) {
        $bin = inet_pton($string);
        if ($bin !== false) {
            return strtoupper(bin2hex($bin));
        }
        return false;
    }
}
