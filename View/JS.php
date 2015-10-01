<?
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
     */
    public static function startup($script) {
        $hash = md5($script);
        if (empty(self::$startup_scripts[$hash])) {
            self::$startup_scripts[$hash] = array('script' => $script, 'rendered' => false);
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
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
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
            $output = '<script language="javascript">lightning={"vars":' . json_encode(self::$vars) . '};</script>';
            self::$vars = array();
            self::$inited = true;
        } elseif (!empty(self::$vars)) {
            $output = '<script language="javascript">$.extend(true, lightning.vars, ' . json_encode(self::$vars) . ');</script>';
        }

        // Include JS files.
        foreach (self::$included_scripts as &$file) {
            $file_name = $file['file'];
            if ($file['versioning']) {
                $concatenator = strpos($file['file'], '?') !== false ? '&' : '?';
                $file_name .= $concatenator . 'v=' . Configuration::get('minified_version', 0);
            }
            if (empty($file['rendered'])) {
                $output .= '<script language="javascript" src="' . $file_name . '" ' . (!empty($file['async']) ? 'async defer' : '');
                if (!empty($file['id'])) {
                    $output .= ' id="' . $file['id'] . '"';
                }
                $output .= '></script>';
                $file['rendered'] = true;
            }
        }

        if (!empty(self::$inline_scripts) || !empty(self::$startup_scripts)) {
            $output .= '<script language="javascript">';
            // Include inline scripts.
            foreach (self::$inline_scripts as $script) {
                if (empty($script['rendered'])) {
                    $output .= $script['script'] . "\n\n";
                    $script['rendered'] = true;
                }
            }

            // Include ready scripts.
            if (!empty(self::$startup_scripts)) {
                $output .= '$(document).ready(function() {';
                foreach (self::$startup_scripts as &$script) {
                    if (empty($script['rendered'])) {
                        $output .= $script['script'] . ';';
                        $script['rendered'] = true;
                    }
                }
                $output .= '})';
            }
            $output .= '</script>';
        }

        return $output;
    }
}
