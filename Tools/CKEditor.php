<?php
/**
 * @file
 * Lightning\Tools\CKEditor
 */

namespace Lightning\Tools;

use Lightning\Tools\Singleton;
use Lightning\View\JS;

/**
 * Contains a wrapper for the CK Editor
 *
 * @package Lightning\Tools
 */
class CKEditor {

    protected static $inited = false;

    /**
     * Add the required JS to the page.
     */
    public static function init() {
        if (!self::$inited) {
            JS::add('/js/ckeditor/ckeditor.js');
            JS::startup('lightning.ckeditors = {}');
            self::$inited = true;
        }
    }

    /**
     * Create an editable div.
     * The field should already be present on the page.
     *
     * @param string $id
     *   The field name / id.
     * @param array $options
     *   A list of options.
     *
     * @return string
     *   The output HTML.
     */
    public static function editableDiv($id, $options) {
        self::init();

        if (!empty($options['finder'])) {
            JS::add('/js/ckfinder/ckfinder.js');
        }

        if (empty($options['content'])) {
            $options['content'] = '<p></p>';
        }
        $spellcheck = !empty($options['spellcheck']) ? 'spellcheck="true"' : '';
        $output = '<div id="' .$id . '" ' . $spellcheck . '>';
        $output .= $options['content'];
        $output .= '</div>';

        return $output;
    }

    /**
     * Build a CK editor in an iframe.
     *
     * @param string $id
     *   The field name / id.
     * @param string $value
     *   The preset value.
     * @param array $options
     *   A list of options.
     *
     * @return string
     *   The output HTML.
     */
    public static function iframe($id, $value, $options = array()) {
        self::init();

        if (!empty($options['finder'])) {
            JS::add('/js/ckfinder/ckfinder.js');
        }

        JS::startup('lightning.ckeditors["' . $id . '"] = CKEDITOR.replace("' . $id . '", ' . json_encode($options) . ');');
        return '<textarea name="' . $id . '" id="' . $id . '">' . Scrub::toHTML($value) . '</textarea>';
    }
}
