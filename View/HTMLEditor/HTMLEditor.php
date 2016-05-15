<?php

namespace Lightning\View\HTMLEditor;

use Lightning\Tools\Configuration;
use Lightning\View\Field\BasicHTML;
use Lightning\View\JS;

class HTMLEditor {

    const TYPE_BASIC = "basic";
    const TYPE_BASIC_IMAGE = "basic_image";
    const TYPE_PRINT = "print";
    const TYPE_FULL = "full";

    protected static $editor;

    protected static function getEditor() {
        if (empty(self::$editor)) {
            self::$editor = Configuration::get('html_editor.editor');
            JS::set('html_editor.editor', self::$editor);
            JS::set('html_editor.browser', Configuration::get('html_editor.browser'));
        }
    }

    public static function init() {
        self::getEditor();
        if (self::$editor == 'ckeditor') {
            CKEditor::init();
        } elseif (self::$editor == 'tinymce') {
            TinyMCE::init();
        }
    }

    public static function div($id, $options = []) {
        self::getEditor();
        if (self::$editor == 'ckeditor') {
            return CKEditor::div($id, $options);
        } elseif (self::$editor == 'tinymce') {
            return TinyMCE::div($id, $options);
        } else {
            return BasicHTML::textarea($id, $options['content'], $options);
        }
    }

    public static function iframe($id, $options = []) {
        self::getEditor();
        if (self::$editor == 'ckeditor') {
            return CKEditor::iframe($id, $options);
        } elseif (self::$editor == 'tinymce') {
            return TinyMCE::iframe($id, $options);
        } else {
            return BasicHTML::textarea($id, $options['content'], $options);
        }
    }
}
