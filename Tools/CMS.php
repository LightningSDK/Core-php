<?php

namespace Lightning\Tools;

use Lightning\Tools\IO\FileManager;
use Lightning\View\JS;
use Lightning\View\Field\Time;

class CMS {

    protected static $settings;

    public static function embed($name, $settings = array()) {
        $content = self::loadCMS($name);
        $content = (!empty($content) ? $content['content'] : (!empty($settings['default']) ? $settings : ''));
        if (ClientUser::getInstance()->isAdmin()) {
            JS::set('token', Session::getInstance()->getToken());
            return
                '<a href="javascript:lightning.cms.edit(\'cms_' . $name . '\')" class="button" id="cms_edit_' . $name . '">Edit</a>'
                . '<a href="javascript:lightning.cms.save(\'cms_' . $name . '\')" class="button hide" id="cms_save_' . $name . '">Save</a>'
                . CKEditor::editableDiv('cms_' . $name,
                    array(
                        'spellcheck' => true,
                        'content' => $content,
                        'finder' => true,
                        'edit_border' => !empty($settings['edit_border']),
                    )
                );
        } else {
            return '<div>' . $content . '</div>';
        }
    }

    public static function initSettings() {
        if (!isset(self::$settings)) {
            self::$settings = Configuration::get('cms', []) + [
                    'location' => 'images'
                ];
        }
    }

    public static function image($name, $settings = array()) {
        self::initSettings();
        $settings += self::$settings;
        $content = self::loadCMS($name);
        if (empty($content)) {
            $content = array(
                'class' => '',
                'content' => !empty($settings['default']) ? $settings['default'] : '',
            );
        }
        $forced_classes = !empty($settings['class']) ? $settings['class'] : '';
        $added_classes = !empty($content['class']) ? $content['class'] : '';
        if (!empty($settings['class'])) {
            $content['class'] .= ' ' . $settings['class'];
        }

        // Needs a file prefix for rendering.
        $handler = FileManager::getFileHandler(!empty($settings['file_handler']) ? $settings['file_handler'] : '', $settings['location']);

        if (ClientUser::getInstance()->isAdmin()) {
            JS::add('/js/ckfinder/ckfinder.js', false);
            JS::set('token', Session::getInstance()->getToken());
            // TODO: This will need extra slashes if using the File handler.
            JS::set('cms.basepath', $settings['location']);
            JS::startup('lightning.cms.initImage();');
            return '<a href="" class="button" onclick="javascript:lightning.cms.editImage(\'' . $name . '\'); return false;">Change</a>'
                . '<a href="" class="button" onclick="javascript:lightning.cms.saveImage(\'' . $name . '\'); return false;">Save</a>'
                . '<input type="text" id="cms_' . $name . '_class" class="imagesCSS" name="' . $forced_classes . '" value="' . $added_classes . '" />'
                . '<img src="' . $handler->getWebURL($content['content']) . '" id="cms_' . $name . '" class="' . $content['class'] .  '" />';
        } else {
            return '<img src="' . $handler->getWebURL($content['content']) . '" class="' . $content['class'] .  '" />';
        }
    }

    public static function plain($name, $settings = array()) {
        if ($content = self::loadCMS($name)) {
            $value = $content['content'];
        } elseif (!empty($settings['default'])) {
            $value = $settings['default'];
        } else {
            $value = '';
        }

        if (ClientUser::getInstance()->isAdmin()) {
            JS::startup('lightning.cms.initPlain()');
            JS::set('token', Session::getInstance()->getToken());
            return '<img src="/images/lightning/pencil.png" class="cms_edit_plain icon-16" id="cms_edit_' . $name . '">'
            . '<img src="/images/lightning/save.png" class="cms_save_plain icon-16" id="cms_save_' . $name . '" style="display:none">'
            . '<input type="text" id="cms_' . $name . '" value="' . $value . '" style="display:none" />'
            . '<span id="cms_display_' . $name . '">' . $value . '</span>';
        } else {
            return $value;
        }
    }

    protected static function loadCMS($name) {
        return $content = Database::getInstance()->selectRow('cms', array('name' => $name));
    }

    protected static function getBaseDir() {
        return '/images/';
    }
    
    public static function date($name, $settings = array()) {
        $content = Database::getInstance()->selectRow($settings['table'], array($settings['key'] => $settings['id']), array($settings['column']));
        if ($content) {
            $value = $content[$settings['column']];
        } else {
            $value = '';
        }
        if (ClientUser::getInstance()->isAdmin()) {
            JS::startup('lightning.cms.initDate()');
            JS::set('token', Session::getInstance()->getToken());
            return '<img src="/images/lightning/pencil.png" class="cms_edit_date icon-16" id="cms_edit_' . $settings['id'] . '">'
            . '<img src="/images/lightning/save.png" class="cms_save_date icon-16" id="cms_save_' . $settings['id'] . '" style="display:none">'
            . '<span id="cms_'.$settings['id'].'" style="display:none">' . Time::datePop('cms_'.$settings['id'], $value, 'true', 0) . '</span>'
            . '<input type="hidden" id="cms_key_' . $settings['id'] . '" value="' . $settings['key'] . '" />'
            . '<input type="hidden" id="cms_column_' . $settings['id'] . '" value="' . $settings['column'] . '" />'
            . '<input type="hidden" id="cms_table_' . $settings['id'] . '" value="' . $settings['table'] . '" />';
        } else {
            return $value;
        }
    }
}
