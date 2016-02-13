<?php

namespace Lightning\View;

use Lightning\Tools\Scrub;

class TinyMCE {
    const TYPE_BASIC = 1;
    const TYPE_BASIC_IMAGE = 2;
    const TYPE_FULL = 3;

    protected static $inited = false;

    public static function init() {
        if (!self::$inited) {
            JS::add('/js/tinymce/tinymce.full.min.js', false);
            JS::startup('lightning.tinymce.init()');
            self::$inited = true;
        }
    }

    public static function editableDiv($id, $options) {
        self::init();

        if (empty($options['content'])) {
            $options['content'] = '<p></p>';
        }

        $spellcheck = !empty($options['spellcheck']) ? 'spellcheck="true"' : '';
        $style = !empty($options['edit_border']) ? ' style="border:1px solid red;"' : '';

        JS::set('tinymce.' . $id, [
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

        if (!empty($options['fullpage'])) {
            $plugins[] = 'fullpage';
        }

        JS::set('tinymce.' . $id, [
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
}
