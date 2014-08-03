<?

namespace Lightning\View;

class CSS {
    protected static $included_files = array();
    protected static $inline_styles = array();
    protected static $startup_styles = array();

    public static function add($file, $type = '') {
        if (empty(self::$included_files[$file])) {
            self::$included_files[$file] = array('file' => $file, 'type' => $type, 'rendered' => false);
        }
    }

    public static function inline($block) {
        $hash = md5($block);
        if (empty(self::$inline_styles[$hash])) {
            self::$inline_styles[$hash] = array('block' => $block, 'rendered' => false);
        }
    }

    public static function render() {
        $output = '';

        foreach (self::$included_files as &$file) {
            if (empty($file['rendered'])) {
                // TODO: add $file[1] for media type. media="screen"
                $output .= '<link rel="stylesheet" type="text/css" href="' . $file['file'] . '" />';
                $file['rendered'] = true;
            }
        }

        if (!empty(self::$inline_styles)) {
            $output .= '<style>';
            foreach (self::$inline_styles as &$style) {
                if (empty($style['rendered'])) {
                    $output .= $style . "\n\n";
                    $style['rendered'] = true;
                }
            }
            $output .= '</style>';
        }

        return $output;
    }
}