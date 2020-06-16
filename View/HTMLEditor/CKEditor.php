<?php
/**
 * @file
 * lightningsdk\core\Tools\CKEditor
 */

namespace lightningsdk\core\View\HTMLEditor;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\View\JS;

/**
 * Contains a wrapper for the CK Editor
 *
 * @package lightningsdk\core\Tools
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
    public static function init() {
        if (!self::$inited) {
            JS::add('/js/ckeditor/ckeditor.js', false);
            JS::startup('lightning.ckeditors = {}');
            JS::startup('lightning.htmleditor.init()');
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
    public static function div($id, $options) {
        self::init();
        self::initSettings($id, $options);

        if (empty($options['content'])) {
            $options['content'] = '<p></p>';
        }

        JS::set('htmleditors.' . $id, $options);

        $spellcheck = !empty($options['spellcheck']) ? 'spellcheck="true"' : '';
        $style = !empty($options['edit_border']) ? ' style="border:1px solid red;"' : '';

        $output = '<input class="html_editor_presave" type="hidden" name="' . $id . '" id="save_' . $id . '" value="' . Scrub::toHTML($options['content']) . '">';
        $output .= '<div id="' .$id . '" ' . $spellcheck . $style . '>';
        $output .= !empty($options['content_rendered']) ? $options['content_rendered'] : $options['content'];
        $output .= '</div>';

        return $output;
    }

    /**
     * Build a CK editor in an iframe.
     *
     * @param string $id
     *   The field name / id.
     * @param array $options
     *   A list of options.
     *
     * @return string
     *   The output HTML.
     */
    public static function iframe($id, $options = []) {
        self::init();
        self::initSettings($id, $options);
        JS::set('htmleditors.' . $id, $options);

        return '<textarea name="' . $id . '" id="' . $id . '">' . Scrub::toHTML($options['content']) . '</textarea>';
    }

    public static function initSettings($id, &$options) {
        // Available Options
        $options['editor_type'] = 'ckeditor';
        if (empty($options['editor'])) {
            $options['editor'] = '';
        }
        switch($options['editor']) {
            // TODO: This needs to be in the base HTML Ed
            case HTMLEditor::TYPE_FULL:         $options['toolbar'] = self::TYPE_FULL;        break;
            case HTMLEditor::TYPE_PRINT:        $options['toolbar'] = self::TYPE_PRINT;       break;
            case HTMLEditor::TYPE_BASIC_IMAGE:  $options['toolbar'] = self::TYPE_BASIC_IMAGE; break;
            case HTMLEditor::TYPE_BASIC:
            default:			                $options['toolbar'] = self::TYPE_BASIC;       break;
        }

        // File Browser
        if (!empty($options['browser'])) {
            switch (Configuration::get('html_editor.browser')) {
                case 'ckfinder':
                    JS::add('/js/ckfinder/ckfinder.js', false, false);
                    JS::startup('CKFinder.setupCKEditor(lightning.ckeditors["' . $id . '"], "/js/ckfinder/")');
                    break;
                case 'elfinder':
                    $parameters = ['type' => 'ckeditor'];
                    if (!empty($options['url']) && $options['url'] == 'full') {
                        $parameters['url'] = 'full';
                        $parameters['web_root'] = Configuration::get('web_root');
                    }
                    $options['filebrowserBrowseUrl'] = '/elfinder?container=images&' . http_build_query($parameters);
                    $options['filebrowserUploadUrl'] = '/imageBrowser?container=images&action=upload&' . http_build_query($parameters);
                    break;
                case 'lightning':
                    $options['filebrowserBrowseUrl'] = '/imageBrowser?container=images';
                    $options['filebrowserUploadUrl'] = '/imageBrowser?container=images&action=upload';
                    break;
            }
        }
    }
}
