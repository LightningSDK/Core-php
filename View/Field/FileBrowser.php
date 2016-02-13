<?php

namespace Lightning\View\Field;

class FileBrowser {
    public static function render($id, $options = []) {
        $output = '';
        if (isset($options['image'])) {
            $class = !empty($options['class']) ? 'class="' . $options['class'] . '"' : '';
            $hidden = empty($options['image']) ? 'style="display:none"' : '';
            $output .= '<img src="' . $options['image'] . '" id="file_browser_image_' . $id . '" ' . $class . ' ' . $hidden . ' />';
        }
        $output .= '<input type="hidden" name="' . $id . '" id="' . $id . '" />';
        $output .= '<span class="button small" onclick="lightning.fileBrowser.openSelect(\'' . $id . '\')">Select</span>';
        if (!isset($options['clear']) || !empty($options['clear'])) {
            $output .= '<span class="button small" onclick="lightning.fileBrowser.clear(\'' . $id . '\')">Clear</span>';
        }
        return $output;
    }
}
