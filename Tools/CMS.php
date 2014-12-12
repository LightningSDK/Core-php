<?php

namespace Lightning\Tools;

use Lightning\View\JS;
use Lightning\View\Field\Time;

class CMS {
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

    public static function image($name, $settings = array()) {
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

        if (ClientUser::getInstance()->isAdmin()) {
            JS::add('/js/ckfinder/ckfinder.js');
            JS::set('token', Session::getInstance()->getToken());
            JS::set('cms.basepath', self::getBaseDir());
            JS::startup('lightning.cms.initImage()');
            return '<a href="" class="button" onclick="javascript:lightning.cms.editImage(\'' . $name . '\'); return false;">Change</a>'
                . '<a href="" class="button" onclick="javascript:lightning.cms.saveImage(\'' . $name . '\'); return false;">Save</a>'
                . '<input type="text" id="cms_' . $name . '_class" class="imagesCSS" name="' . $forced_classes . '" value="' . $added_classes . '" />'
                . '<img src="' . $content['content'] . '" id="cms_' . $name . '" class="' . $content['class'] .  '" />';
        } else {
            return '<img src="' . $content['content'] . '" class="' . $content['class'] .  '" />';
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
        $content = Database::getInstance()->selectRow('affiliate_link', array('affiliate_link_id' => $settings['id']), array('expire'));
        if ($content) {
            $value = $content['expire'];
        } elseif (!empty($settings['default'])) {
            $value = $settings['default'];
        } else {
            $value = '';
        }
        if (ClientUser::getInstance()->isAdmin()) {
            JS::startup('lightning.cms.initDate()');
            JS::set('token', Session::getInstance()->getToken());
            return '<img src="/images/lightning/pencil.png" class="cms_edit_date icon-16" id="cms_edit_' . $settings['id'] . '">'
            . '<img src="/images/lightning/save.png" class="cms_save_date icon-16" id="cms_save_' . $settings['id'] . '" style="display:none">'
            . '<span id="cms_'.$settings['id'].'" style="display:none">' . Time::datePop('cms_'.$settings['id'], $value, 'true', 0) . '</span>';
        } else {
            return $value;
        }
    }
}
