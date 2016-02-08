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

    const TYPE_BASIC = "CKEDITOR.config.toolbar_Basic";
    const TYPE_BASIC_IMAGE = "CKEDITOR.config.toolbar_Basic_Image";
    const TYPE_PRINT = "CKEDITOR.config.toolbar_Print";
    const TYPE_FULL = "CKEDITOR.config.toolbar_Full";

    protected static $inited = false;

    /**
     * Add the required JS to the page.
     */
    public static function init($initCKFinder = false) {
        if (!self::$inited) {
            JS::add('/js/ckeditor/ckeditor.js', false);
            JS::startup('lightning.ckeditors = {}');
            self::$inited = true;
        }
        if ($initCKFinder) {
            JS::add('/js/ckfinder/ckfinder.js', false);
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
            JS::add('/js/ckfinder/ckfinder.js', false, false);
        }

        if (empty($options['content'])) {
            $options['content'] = '<p></p>';
        }

        $spellcheck = !empty($options['spellcheck']) ? 'spellcheck="true"' : '';
        $style = !empty($options['edit_border']) ? ' style="border:1px solid red;"' : '';

        $output = '<div id="' .$id . '" ' . $spellcheck . $style . '>';
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
            $options['filebrowserBrowseUrl'] = '/imageBrowser?container=images';
            $options['filebrowserUploadUrl'] = '/imageBrowser?container=images&action=upload';
            $options['extraPlugins'] = 'uploadimage';
            $options['uploadUrl'] = '/imageBrowser?container=images&action=upload';
        }
        JS::startup('lightning.ckeditors["' . $id . '"] = CKEDITOR.replace("' . $id . '", ' . json_encode($options) . ');');

        return '<textarea name="' . $id . '" id="' . $id . '">' . Scrub::toHTML($value) . '</textarea>';
    }
}
