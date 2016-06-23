<?php
/**
 * @file
 * Includes a class for managing JS files.
 */

namespace Lightning\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Data;
use Lightning\Tools\Session;

/**
 * Class JS
 * @package Lightning\View
 */
class JS {
    /**
     * @var array
     *
     * An array of script files to include.
     */
    protected static $included_scripts = array();

    /**
     * Whether the initial JS has been run.
     *
     * @var boolean
     */
    protected static $inited = false;

    /**
     * @var array
     *
     * A list of scripts to add into a script tag.
     */
    protected static $inline_scripts = array();

    /**
     * @var array
     *
     * A list of vars to be output as a JS object, accessible by JS.
     */
    protected static $vars = array();

    /**
     * @var boolean
     *
     * Whether the funciton lighting_startup() has been added already.
     */
    protected static $startupFunctionAdded = false;

    /**
     * @var array
     *
     * A list of scripts to run when the page is ready.
     */
    protected static $startup_scripts = array();

    /**
     * Add a JS file to be included in the HTML.
     *
     * @param string|array $files
     *   The relative path to the file from the current URL request.
     * @param boolean $async
     *   Whether the script should be loaded asynchronously.
     * @param boolean $versioning
     *   Whether to append a version string when including.
     */
    public static function add($files, $async = true, $versioning = true, $id = '') {
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $path = is_array($file) ? $file['file'] : $file;
            $async = isset($file['async']) ? $file['async'] : $async;
            self::$included_scripts[$path] = array(
                'file' => $path,
                'rendered' => false,
                'async' => $async,
                'versioning' => $versioning,
                'id' => $id,
            );
        }
    }

    /**
     * Add an inline script to run as the page loads or to set variables.
     *
     * @param $script
     *   The javascript code.
     */
    public static function inline($script) {
        $hash = md5($script);
        if (empty(self::$inline_scripts[$hash])) {
            self::$inline_scripts[$hash] = array('script' => $script, 'rendered' => false);
        }
    }

    /**
     * Add an inline script to run when the page is ready.
     *
     * @param string $script
     *   The javascript code.
     * @param array $requires
     *   A list of JS files required before this script runs.
     */
    public static function startup($script, $requires = []) {
        $hash = md5($script);
        if (empty(self::$startup_scripts[$hash])) {
            self::$startup_scripts[$hash] = ['script' => $script, 'requires' => $requires, 'rendered' => false];
        }
    }

    /**
     * Set a javascript variable.
     *
     * @param string $var
     *   The name of the variable.
     * @param mixed $value
     *   The new value.
     */
    public static function set($var, $value) {
        Data::setInPath($var, $value, self::$vars);
    }

    /**
     * Add the session token as a JS accessible variable.
     */
    public static function addSessionToken() {
        self::set('token', Session::getInstance()->getToken());
    }

    /**
     * Output all the JS functions including inline, startup and resource files.
     *
     * @return string
     *   The rendered output.
     */
    public static function render() {
        $output = '';
        if (!self::$inited) {
            $output = '<script>lightning={"vars":' . json_encode(self::$vars) . '};</script>';
            self::$vars = [];
            self::$inited = true;
        } elseif (!empty(self::$vars)) {
            $startup = self::includeStartupFunction();
            $output = '<script>' . $startup . 'lightning_startup(function(){$.extend(true, lightning.vars, ' . json_encode(self::$vars) . ')});</script>';
        }

        // Include JS files.
        foreach (self::$included_scripts as &$file) {
            if (empty($file['rendered'])) {
                $file_name = $file['file'];
                if ($file['versioning']) {
                    $concatenator = strpos($file['file'], '?') !== false ? '&' : '?';
                    $file_name .= $concatenator;
                    if ($version = Configuration::get('minified_version', 0)) {
                        $file_name .= 'v=' .$version;
                    }
                }

                $output .= '<script src="' . $file_name . '" ' . (!empty($file['async']) ? 'async defer' : '');
                if (!empty($file['id'])) {
                    $output .= ' id="' . $file['id'] . '"';
                }
                $output .= '></script>';
                $file['rendered'] = true;
            }
        }

        if (!empty(self::$inline_scripts) || !empty(self::$startup_scripts)) {
            $init_scripts = '';
            // Include inline scripts.
            foreach (self::$inline_scripts as $script) {
                if (empty($script['rendered'])) {
                    $init_scripts .= $script['script'] . ";";
                    $script['rendered'] = true;
                }
            }

            // Include ready scripts.
            if (!empty(self::$startup_scripts)) {
                $ready_scripts = '';
                foreach (self::$startup_scripts as &$script) {
                    if (empty($script['rendered'])) {
                        if (!empty($script['requires'])) {
                            // Include the startup script with the required JS scripts.
                            $ready_scripts .= 'lightning.require(' . json_encode(array_values($script['requires']))
                                . ', function(){' . $script['script'] . '});';
                        } else {
                            $ready_scripts .= $script['script'] . ';';
                        }
                        $script['rendered'] = true;
                    }
                }
                $init_scripts .= self::includeStartupFunction();
                if (!empty($ready_scripts)) {
                    $init_scripts .= 'lightning_startup(function() {' . $ready_scripts . '})';
                }
            }
            if (!empty($init_scripts)) {
                $output .= '<script>' . $init_scripts . '</script>';
            }
        }

        return $output;
    }

    protected static function includeStartupFunction() {
        if (!self::$startupFunctionAdded) {
            self::$startupFunctionAdded = true;
            return 'function lightning_startup(callback) { if (typeof $ == "undefined") { setTimeout(function(){ lightning_startup(callback); }, 500) } else $().ready(callback) }';
        }
        return '';
    }
}
