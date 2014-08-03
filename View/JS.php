<?

namespace Lightning\View;

class JS {
    protected static $included_scripts = array();
    protected static $inline_scripts = array();
    protected static $startup_scripts = array();

    public static function add($file) {
        self::$included_scripts[$file] = array('file' => $file, 'rendered' => false);
    }

    public static function inline($script) {
        $hash = md5($script);
        if (empty(self::$inline_scripts[$hash])) {
            self::$inline_scripts[$hash] = array('script' => $script, 'rendered' => false);
        }
    }

    public static function startup($script) {
        $hash = md5($script);
        if (empty(self::$startup_scripts[$hash])) {
            self::$startup_scripts[$hash] = array('script' => $script, 'rendered' => false);
        }
    }

    public static function render() {
        $output = '';

        foreach (self::$included_scripts as &$file) {
            if (empty($file['rendered'])) {
                $output .= '<script language="javascript" src="' . $file['file'] . '"></script>';
                $file['rendered'] = true;
            }
        }

        if (!empty(self::$inline_scripts) || !empty(self::$startup_scripts)) {
            $output .= '<script language="javascript">';
            foreach (self::$inline_scripts as $script) {
                if (empty($script['rendered'])) {
                    $output .= $script['script'] . "\n\n";
                    $script['rendered'] = true;
                }
            }

            if (!empty(self::$startup_scripts)) {
                $output .= '$(function(){';
                foreach (self::$startup_scripts as &$script) {
                    if (empty($script['rendered'])) {
                        $output .= $script['script'];
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
