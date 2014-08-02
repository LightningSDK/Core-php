<?

namespace Lightning\View;

class CSS {
    protected static $included_files = array();
    protected static $inline_styles = array();
    protected static $startup_styles = array();

    public static function add($file, $type = '') {
        self::$included_files[$file] = array($file, $type);
    }

    public static function inline($script) {
        self::$inline_styles[] = $script;
    }

    public static function render() {
        $output = '';

        foreach (self::$included_files as $file) {
            // TODO: add $file[1] for media type. media="screen"
            $output .= '<link rel="stylesheet" type="text/css" href="' . $file[0] . '" />';
        }

        if (!empty(self::$inline_styles) || !empty(self::$startup_styles)) {
            $output .= '<style>';
            foreach (self::$inline_styles as $script) {
                $output .= $script . "\n\n";
            }
            $output .= '</style>';
        }

        return $output;
    }
}