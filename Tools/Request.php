<?php

namespace Overridable\Lightning\Tools;

use Lightning\Tools\Data;
use Lightning\Tools\Scrub;

class Request {

    const X_FORWARDED_FOR = 'X-Forwarded-For';
    const X_FORWARDED_PROTO = 'X-Forwarded-Proto';
    const IP = 'ip';
    const IP_INT = 'ip_int';

    const TYPE_BOOLEAN_INT = 'boolean-int';
    const TYPE_EXPLODE = 'explode';
    const TYPE_ARRAY = 'array';
    const TYPE_ARRAY_KEYS = 'array_keys';
    const TYPE_KEYED_ARRAY = 'keyed_array';
    const TYPE_ASSOC_ARRAY = 'assoc_array';
    const TYPE_URL = 'url';
    const TYPE_EMAIL = 'email';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_HEX = 'hex';
    const TYPE_INT = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_BASE64 = 'base64';
    const TYPE_ENCRYPTED = 'encrypted';
    const TYPE_HTML = 'html';
    const TYPE_JSON = 'json';
    const TYPE_JSON_STRING = 'json_string';
    const TYPE_URL_ENCODED = 'url_encoded';
    const TYPE_TEXT = 'text';
    const TYPE_STRING = 'string';

    /**
     * A list of request headers.
     *
     * @var array
     */
    protected static $headers = [];

    /**
     * The parsed input from a posted JSON string.
     *
     * @var array
     */
    protected static $parsedInput = null;

    /**
     * Get the HTTP request type.
     *
     * @return string
     *   The request type: POST, GET, DELETE, etc.
     */
    public static function type() {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Detect whether the current request was made over https.
     *
     * @return boolean
     */
    public static function isHTTPS() {
        return !empty($_SERVER['HTTPS']) || static::getHeader(static::X_FORWARDED_PROTO) == 'https';
    }

    /**
     * Determine if the request is made from the command line.
     * This can be either CLI routes or Jobs.
     *
     * @return boolean
     *
     * @TODO need a more accurate way to determine this on other systems.
     */
    public static function isCLI() {
        return PHP_SAPI == 'cli';
    }

    public static function getHeader($header) {
        $nginx_header = 'HTTP_' . strtoupper(preg_replace('/-/', '_', $header));
        if (isset($_SERVER[$nginx_header])) {
            return $_SERVER[$nginx_header];
        } else {
            if (function_exists('apache_request_headers')) {
                if (empty(static::$headers)) {
                    static::$headers = apache_request_headers();
                    if (!empty(static::$headers[$header])) {
                        return static::$headers[$header];
                    }
                }
            }
        }
        return false;
    }

    /**
     * Returns the current location, stripping any slashes.
     *
     * @return string
     *   The location.
     */
    public static function getLocation() {
        return trim(static::query('request'), '/');
    }

    public static function getURL() {
        return (static::isHTTPS() ? 'https://' : 'http://') . static::getDomain() . '/' . static::getLocation();
    }

    public static function getDomain() {
        return !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (!empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
    }

    /**
     * Convert a requested action to a controller method name.
     *
     * @param string $prefix
     *   The prefix to add. 'execute' for CLI or 'post' or 'get' for Page requests.
     * @param string $action
     *   The requested action. May be hyphen-ated or camelCase.
     *
     * @return string
     *   A proper camelcase function name with the prefix.
     */
    public static function convertFunctionName($prefix, $action) {
        return $prefix . str_replace(' ', '', ucwords(preg_replace('/[-_]/', ' ', $action)));
    }

    /**
     * Get a parameter from a URL.
     *
     * @param string $regex
     *   The regex expression for the parameter.
     *
     * @return mixed
     *   The sanitized variable.
     */
    public static function getFromURL($regex) {
        $args = func_get_args();

        preg_match($regex, static::getLocation(), $matches);
        if (isset($matches[1])) {
            $args[0] = $matches[1];
            return call_user_func_array('self::clean', $args);
        } else {
            return null;
        }
    }

    /**
     * Access GET/POST inputs from request.
     *
     * @param $var
     *   The name of the variable from $_REQUEST
     * @param string $type
     *   The type of data to allow. This will default to plain text.
     * @param $subtype
     *   If $type is an array, subtype can be used to process the values inside.
     * @param mixed $default
     *   A default value if none is set.
     *
     * @return mixed
     *   value or false if none.
     */
    public static function get($var, $type = '', $subtype = '', $default = null) {
        $value = Data::getFromPath($var, $_POST);
        if ($value === null) {
            $value = Data::getFromPath($var, $_GET);
        }
        if ($value === null) {
            return $default;
        }

        $args = func_get_args();
        $args[0] = $value;
        return call_user_func_array('self::clean', $args);
    }

    /**
     * Get a value from a cookie.
     *
     * @param $var
     * @param string $type
     * @param $subtype
     *
     * @return bool|float|int|string
     */
    public static function cookie($var, $type='', $subtype='', $default = null) {
        $value = Data::getFromPath($var, $_COOKIE);
        if ($value === null) {
            return $default;
        }

        $args = func_get_args();
        $args[0] = $value;
        return call_user_func_array('self::clean', $args);
    }

    /**
     * Gets a variable only if it's in the $_POST global.
     *
     * @param $var
     * @param string $type
     * @param $subtype
     *
     * @return bool|float|int|string
     */
    public static function post($var, $type='', $subtype='', $default = null) {
        $value = Data::getFromPath($var, $_POST);
        if ($value === null) {
            return $default;
        }

        $args = func_get_args();
        $args[0] = $value;
        return call_user_func_array('static::clean', $args);
    }

    /**
     * Gets a variable only if it's in the $_GET global.
     *
     * @param $var
     * @param string $type
     * @param $subtype
     * @param default
     *
     * @return mixed
     */
    public static function query($var, $type='', $subtype='', $default = null) {
        $value = Data::getFromPath($var, $_GET);
        if ($value === null) {
            return $default;
        }

        $args = func_get_args();
        $args[0] = $value;
        return call_user_func_array('self::clean', $args);
    }

    /**
     * Get a variable from posted json data.
     *
     * @param $var
     * @param string $type
     * @param $subtype
     * @param default
     *
     * @return mixed
     */
    public static function json($var, $type='', $subtype='', $default = null) {
        if (self::$parsedInput === null && $json = file_get_contents('php://input')) {
            self::$parsedInput = json_decode($json, true) ?: [];
        }

        if (!isset(self::$parsedInput[$var])) {
            return $default;
        }
        $args = func_get_args();
        $args[0] = self::$parsedInput[$var];
        return call_user_func_array('self::clean', $args);
    }

    /**
     * Get a POST/GET if set, if not check for a cookie by the same name.
     *
     * @param string $var
     * @param string $type
     * @param string $subtype
     * @param mixed $default
     *
     * @return bool|float|int|string
     */
    public static function getAny($var, $type = '', $subtype = '', $default = null) {
        $value = Data::getFromPath($var, $_REQUEST);
        if ($value === null) {
            $value = Data::getFromPath($var, $_COOKIE);
        }
        if ($value === null) {
            return $default;
        }

        $args = func_get_args();
        $args[0] = $value;
        return call_user_func_array('self::clean', $args);
    }

    /**
     * Get a server variable with known handling.
     *
     * @param string $var
     *   The name of the variable.
     *
     * @return mixed
     */
    public static function server($var) {
        switch ($var) {
            case self::IP_INT:
                $ip = self::getIP();
                return $ip ? ip2long($ip) : 0;
            case self::IP:
                return self::getIP() ?: '';
            default:
                return isset($_SERVER[$var]) ? $_SERVER[$var] : null;
        }
    }

    /**
     * Get the client IP address.
     *
     * @return string
     *   IP address.
     */
    protected static function getIP() {
        $forwarded_header = self::getHeader(self::X_FORWARDED_FOR);
        return $forwarded_header ?: $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Clean any data before it's returned.
     *
     * @param $data
     *   The value of the parameter.
     * @param $type
     *   The type of data to scrub the input.
     *
     * @return bool|float|int|string
     */
    protected static function clean($data, $type = 'text') {
        if (get_magic_quotes_gpc()) {
            $data = stripslashes($data);
        }

        // Return the value.
        switch($type) {
            case self::TYPE_BOOLEAN_INT:
                return intval(Scrub::boolean($data));
                break;
            case self::TYPE_EXPLODE:
                $data = trim($data, ',');
                if ($data === "") {
                    return [];
                }
                $data = explode(',', $data);
            case self::TYPE_ARRAY:
            case self::TYPE_ARRAY_KEYS:
                $args = func_get_args();
                if (!is_array($data) || count($data) == 0)
                    return false;
                $output = [];
                foreach($data as $k => $v) {
                    $output[] = self::clean(
                        $type == 'array_keys' ? $k : $v,
                        !empty($args[2]) ? $args[2] : null
                    );
                }
                return $output;
                break;
            case self::TYPE_KEYED_ARRAY:
                $args = func_get_args();
                if (!is_array($data) || count($data) == 0)
                    return false;
                $output = [];
                foreach($data as $k => $v) {
                    $output[$k] = self::clean(
                        $v,
                        !empty($args[2]) ? $args[2] : null
                    );
                }
                return $output;
                break;
            case self::TYPE_ASSOC_ARRAY:
                if (!is_array($data) || count($data) == 0) {
                    return false;
                }
                return $data;
            case self::TYPE_URL:
            case self::TYPE_EMAIL:
            case self::TYPE_BOOLEAN:
            case self::TYPE_HEX:
            case self::TYPE_INT:
            case self::TYPE_FLOAT:
            case self::TYPE_DECIMAL:
            case self::TYPE_BASE64:
            case self::TYPE_ENCRYPTED:
            case self::TYPE_HTML:
            case self::TYPE_JSON:
            case self::TYPE_JSON_STRING:
                $args = func_get_args();
                // It's possible that a + was changed to a space in URL decoding.
                if ($type == 'base64' || $type == 'encrypted') {
                    $args[0] = str_replace(' ', '+', $args[0]);
                }
                // Remove the second item, the type.
                if (count($args) > 2) {
                    unset($args[1]);
                    $args = array_values($args);
                }
                return call_user_func_array("Lightning\\Tools\\Scrub::{$type}", $args);
                break;
            case self::TYPE_URL_ENCODED:
                return urldecode($data);
                break;
            case self::TYPE_TEXT:
                // This still allows some basic HTML.
                return Scrub::text($data);
                break;
            case self::TYPE_STRING:
            default:
                // This does nothing to the string. Assume it is not sanitized.
                return $data;
                break;
        }
    }
}
