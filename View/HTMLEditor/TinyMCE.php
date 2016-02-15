<?php

namespace Lightning\View\HTMLEditor;

use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;
use Lightning\View\JS;

class TinyMCE {
    const TYPE_BASIC = 1;
    const TYPE_BASIC_IMAGE = 2;
    const TYPE_FULL = 3;

    protected static $inited = false;

    public static function init() {
        if (!self::$inited) {
            JS::add('/js/tinymce/tinymce.min.js', false);
            JS::startup('lightning.htmleditor.init()');
            self::$inited = true;
        }
    }

    public static function div($id, $options) {
        self::init();
        self::initSettings($id, $options);

        if (empty($options['content'])) {
            $options['content'] = '<p></p>';
        }

        $spellcheck = !empty($options['spellcheck']) ? 'spellcheck="true"' : '';
        $style = !empty($options['edit_border']) ? ' style="border:1px solid red;"' : '';

        JS::set('htmleditors.' . $id, [
            'selector' => '#' . $id,
            'startup' => !empty($options['startup']),
            'inline' => true,
            'plugins' => self::getPlugins(),
            'toolbar' => 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code | yt | columns',
            'visualblocks_default_state' => true,
            'images_upload_url' => '/imageBrowser',
            'automatic_uploads' => true,
            'browser' => !empty($options['browser']),
            'browser_container' => 'images',
            'relative_urls' => false,
            // remove_script_host for editing emails?
        ]);

        $output = '<div id="' .$id . '" ' . $spellcheck . $style . '>';
        $output .= $options['content'];
        $output .= '</div>';

        return $output;
    }

    public static function iframe($id, $options = []) {
        self::init();
        self::initSettings($id, $options);

        if (!empty($options['fullpage'])) {
            $plugins[] = 'fullpage';
        }

        JS::set('htmleditors.' . $id, [
            'selector' => '#' . $id,
            'inline' => false,
            'plugins' => self::getPlugins(),
            'toolbar' => 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code | yt | columns',
            'visualblocks_default_state' => true,
            'browser' => !empty($options['browser']),
            'browser_container' => 'images',
            'relative_urls' => false,
        ]);

        return '<textarea name="' . $id . '" id="' . $id . '">' . Scrub::toHTML($options['content']) . '</textarea>';
    }

    protected static function getPlugins() {
        return [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen hr',
            'insertdatetime media table contextmenu paste code visualblocks'
        ];
    }

    public static function initSettings($id, &$options) {
        $options['editor_type'] = 'tinymce';
        if (!empty($options['browser'])) {
            switch (Configuration::get('html_editor.browser')) {
                case 'CKFinder':
                    JS::add('/js/ckfinder/ckfinder.js', false, false);
                    JS::startup('CKFinder.setupCKEditor(lightning.ckeditors["' . $id . '"], "/js/ckfinder/")');
                    break;
                case 'elFinder':
                    $options['filebrowserBrowseUrl'] = '/js/elFinder?container=images';
                    $options['filebrowserUploadUrl'] = '/js/elFinder?container=images&action=upload';
                    $options['extraPlugins'] = 'uploadimage';
                    $options['uploadUrl'] = '/js/imageBrowser?container=images&action=upload';
                    break;
                case 'lightning':
                    $options['filebrowserBrowseUrl'] = '/imageBrowser?container=images';
                    $options['filebrowserUploadUrl'] = '/imageBrowser?container=images&action=upload';
                    $options['extraPlugins'] = 'uploadimage';
                    $options['uploadUrl'] = '/imageBrowser?container=images&action=upload';
                    break;
            }
        }
    }
}
