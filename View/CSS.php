<?php

namespace Lightning\View;

use Lightning\Tools\Configuration;

class CSS {
    protected static $included_files = array();
    protected static $inline_styles = array();
    protected static $startup_styles = array();

    /**
     * Add a CSS file to be included in the HTML.
     *
     * @param string|array $files
     *   The relative path to the file from the current URL request.
     */
    public static function add($files, $type = '') {
        if (!is_array($files)) {
            $files = [$files];
        }
        foreach ($files as $file) {
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
                $file_name = $file['file'] . '?v=' . Configuration::get('minified_version', 0);
                // TODO: add $file[1] for media type. media="screen"
                $output .= '<link rel="stylesheet" type="text/css" href="' . $file_name . '" />';
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