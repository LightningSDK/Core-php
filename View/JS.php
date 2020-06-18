<?php
/**
 * @file
 * Includes a class for managing JS files.
 */

namespace lightningsdk\core\View;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Data;
use lightningsdk\core\Tools\Form as FormTool;

/**
 * Class JS
 * @package lightningsdk\core\View
 */
class JS {
    /**
     * @var array
     *
     * An array of script files to include.
     */
    protected static $included_scripts = [];

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
    protected static $inline_scripts = [];

    /**
     * @var array
     *
     * A list of vars to be output as a JS object, accessible by JS.
     */
    protected static $vars = [];

    /**
     * @var array
     *
     * A list of scripts to run when the page is ready.
     */
    protected static $startup_scripts = [];

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
        $files = self::getCompiledScripts($files);

        foreach ($files as $file) {
            $path = is_array($file) ? $file['file'] : $file;
            $async = isset($file['async']) ? $file['async'] : $async;
            self::$included_scripts[$path] = [
                'file' => $path,
                'rendered' => false,
                'async' => $async,
                'versioning' => $versioning,
                'id' => $id,
            ];
        }
    }

    protected static function getCompiledScripts($resources) {
        $compiled_scripts = [];
        if (!is_array($resources)) {
            $resources = [$resources];
        }
        foreach ($resources as $module => $scripts) {
            if (is_numeric($module)) {
                // If ths index is numeric, this is not a resource but a literal script name.
                $compiled_scripts[] = $scripts;
            } else {
                if (!is_array($scripts)) {
                    $scripts = [$scripts];
                }

                $modules_files = Configuration::get('compiler.js.' . $module);
                foreach ($scripts as $script) {
                    if (empty($modules_files[$script])) {
                        throw new \Exception('Compiled JS reference not found for: ' . $script);
                    }
                    $dest_file = is_array($modules_files[$script]) ? $modules_files[$script]['dest'] : $modules_files[$script];
                    $compiled_scripts[] = '/js/' . $dest_file;
                }
            }
        }
        return array_unique($compiled_scripts);
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
            self::$inline_scripts[$hash] = ['script' => $script, 'rendered' => false];
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
            if (!empty($requires)) {
                $requires = self::getCompiledScripts($requires);
            } else {
                $requires = ['lightning'];
            }
            self::$startup_scripts[$hash] = ['script' => $script, 'requires' => $requires, 'rendered' => false];
        }
    }

    public static function getStartups() {
        return self::$startup_scripts;
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

    public static function push($var, $value) {
        Data::pushInPath($var, $value, self::$vars);
    }

    public static function getAll() {
        return self::$vars;
    }

    /**
     * Add the session token as a JS accessible variable.
     */
    public static function addSessionToken() {
        self::set('token', FormTool::getToken());
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
            self::$vars['minified_version'] = Configuration::get('minified_version', 0);
            $output = '<script>';
            $output .= self::includeStartupFunction();
            if (!empty(self::$vars)) {
                $output .= 'lightning={"vars":' . json_encode(self::$vars) . '};';
            }
            $output .= '</script>';
            self::$vars = [];
            self::$inited = true;
        }

        // Include JS files.
        foreach (self::$included_scripts as &$file) {
            if (empty($file['rendered'])) {
                $file_name = $file['file'];
                if ($file['versioning'] && $version = Configuration::get('minified_version', 0)) {
                    $concatenator = strpos($file['file'], '?') !== false ? '&' : '?';
                    $file_name .= $concatenator . 'v=' .$version;
                }

                $output .= '<script src="' . $file_name . '" ' . (!empty($file['async']) ? 'async defer' : '');
                if (!empty($file['id'])) {
                    $output .= ' id="' . $file['id'] . '"';
                }
                $output .= '></script>';
                $file['rendered'] = true;
            }
        }

        $init_scripts = '';
        // Include inline scripts.
        foreach (self::$inline_scripts as $script) {
            if (empty($script['rendered'])) {
                $init_scripts .= $script['script'] . ";\n";
                $script['rendered'] = true;
            }
        }

        // Include ready scripts.
        if (!empty(self::$startup_scripts)) {
            foreach (self::$startup_scripts as &$script) {
                if (empty($script['rendered'])) {
                    // Include the startup script with the required JS scripts.
                    $init_scripts .= '$_startup(' . json_encode($script['requires']) . ', function(){' . $script['script'] . '});'."\n";
                    $script['rendered'] = true;
                }
            }
        }

        if (!empty($init_scripts)) {
            $output .= '<script>' . $init_scripts . '</script>';
        }

        return $output;
    }

    protected static function includeStartupFunction() {
        return '
           $lsq = [];
           $_startup = function(req, cb){
             if (typeof lightning != "undefined" && typeof lightning.js != "undefined") {
               lightning.js.require(req, cb);
             } else {
               $lsq.push({r: req, c: cb});
             }
           };';
    }
}
