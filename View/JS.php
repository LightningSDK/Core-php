<?
/**
 * @file
 * Includes a class for managing JS files.
 */

namespace Lightning\View;
use Overridable\Lightning\Tools\Session;

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

    protected static $vars = array();

    /**
     * @var array
     *
     * A list of scripts to run when the page is ready.
     */
    protected static $startup_scripts = array(
        'coreInit' => array(
            'script' => 'lightning.startup.init();',
            'rendered' => false,
        ),
    );

    /**
     * Add a JS file to be included in the HTML.
     *
     * @param $file
     *   The relative path to the file from the current URL request.
     */
    public static function add($file) {
        self::$included_scripts[$file] = array('file' => $file, 'rendered' => false);
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

    public static function set($var, $value) {
        $var = explode('.', $var);
        self::setSubPath($var, $value, self::$vars);
    }

    protected static function setSubPath($var, $value, &$container) {
        if (count($var) == 1) {
            $container[$var[0]] = $value;
        } else {
            $top_var = $var[0];
            if (!isset($container[$top_var]) || !is_array($container[$top_var])) {
                $container[$top_var] = array();
            }
            array_shift($var);
            self::setSubPath($var, $value, $container[$top_var]);
        }
    }

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
            if (empty($file['rendered'])) {
                $output .= '<script language="javascript" src="' . $file['file'] . '"></script>';
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
                $output .= '$(document).ready(function(){';
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
