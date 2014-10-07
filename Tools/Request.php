<?php

namespace Lightning\Tools;

use Lightning\Tools\Scrub;

class Request {
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
     * Convert a reuqested action to a controller method name.
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
        return $prefix . str_replace(' ', '', ucwords(str_replace('-', ' ', $action)));
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
        preg_match($regex, $_GET['request'], $matches);
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
    public static function get($var, $type='', $subtype='', $default=null){
        if(!isset($_REQUEST[$var]))
            return $default;

        $args = func_get_args();
        $args[0] = $_REQUEST[$var];
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
    public static function cookie($var, $type='', $subtype='', $default=null){
        if(!isset($_COOKIE[$var]))
            return $default;

        $args = func_get_args();
        $args[0] = $_COOKIE[$var];
        return call_user_func_array('self::clean', $args);
    }

    /**
     * Gets a variable only if it's in the $_GET global.
     *
     * @param $var
     * @param string $type
     * @param $subtype
     *
     * @return bool|float|int|string
     */
    public static function post($var, $type='', $subtype='', $default=null){
        if(!isset($_POST[$var]))
            return $default;

        $args = func_get_args();
        $args[0] = $_POST[$var];
        return call_user_func_array('self::clean', $args);
    }

    /**
     * Gets a variable only if it's in the $_GET global.
     *
     * @param $var
     * @param string $type
     * @param $subtype
     *
     * @return bool|float|int|string
     */
    public static function query($var, $type='', $subtype='', $default=null){
        if(!isset($_GET[$var]))
            return $default;

        $args = func_get_args();
        $args[0] = $_GET[$var];
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
    public static function getAny($var, $type='', $subtype=''){
        if(isset($_REQUEST[$var])) {
            $args = func_get_args();
            $args[0] = $_REQUEST[$var];
            return call_user_func_array('self::clean', $args);
        }

        if(!isset($_COOKIE[$var])) {
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
                return ip2long($_SERVER['REMOTE_ADDR']);
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
    private static function clean($data, $type = 'text'){
        // Return the value.
        switch($type) {
            case 'int':
                return intval($data);
                break;
            case 'float':
                return floatval($data);
                break;
            case 'boolean-int':
                return intval(Scrub::boolean($data));
                break;
            case 'explode':
                $data = explode(',', $data);
            case 'array':
            case 'array_keys':
                $args = func_get_args();
                if(!is_array($data) || count($data) == 0)
                    return false;
                $output = array();
                foreach($data as $k => $v){
                    $output[] = self::clean($type == 'array_keys' ? $k : $v, $args[2]);
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
                // Remove the second item, the type.
                if (count($args) > 2) {
                    unset($args[1]);
                    $args = array_values($args);
                }
                return call_user_func_array("Lightning\\Tools\\Scrub::{$type}", $args);
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
