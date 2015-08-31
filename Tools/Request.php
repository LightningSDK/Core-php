<?php

namespace Overridable\Lightning\Tools;

use Lightning\Tools\Scrub;

class Request {

    const X_FORWARDED_FOR = 'X-Forwarded-For';
    const X_FORWARDED_PROTO = 'X-Forwarded-Proto';

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
        $location = static::getLocation();
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
     *
     * @return mixed
     *   value or false if none.
     */
    public static function get($var, $type = '', $subtype = '', $default = null) {
        if (isset($_POST[$var])) {
            $args = func_get_args();
            $args[0] = $_POST[$var];
            return call_user_func_array('self::clean', $args);
        }

        if (isset($_GET[$var])) {
            $args = func_get_args();
            $args[0] = $_GET[$var];
            return call_user_func_array('self::clean', $args);
        }

        else {
            return $default;
        }
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
        if (!isset($_COOKIE[$var]))
            return $default;

        $args = func_get_args();
        $args[0] = $_COOKIE[$var];
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
        if (!isset($_POST[$var]))
            return $default;

        $args = func_get_args();
        $args[0] = $_POST[$var];
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
        if (!isset($_GET[$var]))
            return $default;

        $args = func_get_args();
        $args[0] = $_GET[$var];
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
            self::$parsedInput = json_decode($json, true) ?: array();
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
     * @param $var
     * @param string $type
     * @param $subtype
     *
     * @return bool|float|int|string
     */
    public static function getAny($var, $type='', $subtype='') {
        if (isset($_REQUEST[$var])) {
            $args = func_get_args();
            $args[0] = $_REQUEST[$var];
            return call_user_func_array('self::clean', $args);
        }

        if (isset($_COOKIE[$var])) {
            $args = func_get_args();
            $args[0] = $_COOKIE[$var];
            return call_user_func_array('self::clean', $args);
        }

        return false;
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
            case 'ip_int':
                return empty($_SERVER['REMOTE_ADDR']) ? 0 : ip2long($_SERVER['REMOTE_ADDR']);
            case 'ip':
                return empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR'];
            default:
                return isset($_SERVER[$var]) ? $_SERVER[$var] : null;
        }
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
            case 'int':
                return Scrub::int($data);
                break;
            case 'float':
                return Scrub::float($data);
                break;
            case 'boolean-int':
                return intval(Scrub::boolean($data));
                break;
            case 'explode':
                $data = explode(',', trim($data, ','));
            case 'array':
            case 'array_keys':
                $args = func_get_args();
                if (!is_array($data) || count($data) == 0)
                    return false;
                $output = array();
                foreach($data as $k => $v) {
                    $output[] = self::clean(
                        $type == 'array_keys' ? $k : $v,
                        !empty($args[2]) ? $args[2] : null
                    );
                }
                return $output;
                break;
            case 'keyed_array':
                $args = func_get_args();
                if (!is_array($data) || count($data) == 0)
                    return false;
                $output = array();
                foreach($data as $k => $v) {
                    $output[$k] = self::clean(
                        $v,
                        !empty($args[2]) ? $args[2] : null
                    );
                }
                return $output;
                break;
            case 'url':
            case 'email':
            case 'boolean':
            case 'hex':
            case 'base64':
            case 'encrypted':
            case 'html':
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
            case 'urlencoded':
                return urldecode($data);
                break;
            case 'text':
                // This still allows some basic HTML.
                return Scrub::text($data);
                break;
            case 'string':
            default:
                // This does nothing to the string. Assume it is not sanitized.
                return $data;
                break;
        }
    }
}
