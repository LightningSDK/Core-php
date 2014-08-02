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
     * Access GET/POST inputs from request.
     *
     * @param $var
     *  The name of the variable from $_REQUEST
     * @param string $type
     *  The type of data to allow. This will default to plain text.
     * @param $subtype
     *
     * @return value or false if none.
     */
    public static function get($var, $type='', $subtype=''){
        if(!isset($_REQUEST[$var]))
            return false;

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
    public static function cookie($var, $type='', $subtype=''){
        if(!isset($_COOKIE[$var]))
            return false;

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
    public static function post($var, $type='', $subtype=''){
        if(!isset($_POST[$var]))
            return false;

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
    public static function query($var, $type='', $subtype=''){
        if(!isset($_GET[$var]))
            return false;

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
            case 'hex':
                return Scrub::hex($data);
                break;
            case 'int':
                return intval($data);
                break;
            case 'float':
                return floatval($data);
                break;
            case 'email':
                return Scrub::email($data);
                break;
            case 'boolean':
                return Scrub::boolean($data);
                break;
            case 'boolean-int':
                return intval(Scrub::boolean($data));
                break;
            case 'array':
                return Scrub::_array($data);
                break;
            case 'explode':
                $data = explode(',', $data);
            case 'array':
                $args = func_get_args();
                if(!is_array($data) || count($data) == 0)
                    return false;
                $output = array();
                foreach($data as $d){
                    $output[] = self::clean($d, $args[2]);
                }
                return $output;
                break;
            case 'html':
                call_user_func_array('Lightning\Tools\Scrub::html', array_slice(func_get_args(), 1));
                return Scrub::html($data);
                break;
            case 'text':
            default:
                return Scrub::text($data);
                break;
        }
    }
}
