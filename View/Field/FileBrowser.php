<?php

namespace Lightning\View\Field;

use Lightning\Tools\Configuration;
use Lightning\View\JS;

class FileBrowser {
    public static function render($id, $options = []) {
        JS::set('fileBrowser.type', Configuration::get('imageBrowser.type'));
        $output = '';
        if (isset($options['image'])) {
            $class = !empty($options['class']) ? 'class="' . $options['class'] . '"' : '';
            $hidden = empty($options['image']) ? 'style="display:none"' : '';
            $output .= '<img src="' . $options['image'] . '" id="file_browser_image_' . $id . '" ' . $class . ' ' . $hidden . ' />';
        }
        $output .= '<input type="hidden" name="' . $id . '" id="' . $id . '" />';
        $output .= '<span class="button small" onclick="lightning.fileBrowser.openSelect(\'lightning-field\', \'' . $id . '\')">Select</span>';
        if (!isset($options['clear']) || !empty($options['clear'])) {
            $output .= '<span class="button small" onclick="lightning.fileBrowser.clear(\'' . $id . '\')">Clear</span>';
        }
        return $output;
    }
}
