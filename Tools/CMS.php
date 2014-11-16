<?php

namespace Lightning\Tools;

use Lightning\View\JS;

class CMS {
    public static function embed($name, $default_value = '') {
        $content = self::loadCMS($name);
        $content = (!empty($content) ? $content['content'] : $default_value);
        if (ClientUser::getInstance()->isAdmin()) {
            JS::set('token', Session::getInstance()->getToken());
            return
                '<a href="javascript:lightning.cms.edit(\'cms_' . $name . '\')" class="button" id="cms_edit_' . $name . '">Edit</a>'
                . '<a href="javascript:lightning.cms.save(\'cms_' . $name . '\')" class="button hide" id="cms_save_' . $name . '">Save</a>'
                . CKEditor::editableDiv('cms_' . $name,
                    array('spellcheck' => true, 'content' => $content, 'finder' => true)
                );
        } else {
            return '<div>' . $content . '</div>';
        }
    }

    public static function image($name, $settings = array()) {
        $content = self::loadCMS($name);
        if (empty($content)) {
            $content = array(
                'class' => '',
                'content' => !empty($settings['default']) ? $settings['default'] : '',
            );
        }
        if (!empty($settings['class'])) {
            $content['class'] .= ' ' . $settings['class'];
        }

        if (ClientUser::getInstance()->isAdmin()) {
            JS::set('token', Session::getInstance()->getToken());
        } else {
            return '<img src="' . $content['content'] . '" class="' . $content['class'] .  '" />';
        }
    }

    protected static function loadCMS($name) {
        return $content = Database::getInstance()->selectRow('cms', array('name' => $name));
    }
}
