<?php

namespace Lightning\Tools;

use Lightning\Tools\Singleton;
use Lightning\View\JS;

class CKEditor extends Singleton {
    public function __construct() {
        JS::add('/js/ckeditor/ckeditor.js');
    }

    public static function editableDiv($id, $options) {
        if (empty($options['content'])) {
            $options['content'] = '<p></p>';
        }
        $spellcheck = !empty($options) ? 'spellcheck="true"' : '';
        $output = '<div id="' .$id . '" ' . $spellcheck . '>';
        $output .= $options['content'];
        $output .= '</div>';

        return $output;
    }
}
