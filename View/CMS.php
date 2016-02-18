<?php

namespace Lightning\View;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\IO\FileManager;
use Lightning\Tools\Session;
use Lightning\Model\CMS as CMSModel;
use Lightning\View\HTMLEditor\HTMLEditor;

class CMS {

    protected static $settings;

    public static function embed($name, $settings = array()) {
        $content = CMSModel::loadByName($name);
        $content = (!empty($content) ? $content->content : (!empty($settings['default']) ? $settings['default'] : ''));
        if (ClientUser::getInstance()->isAdmin()) {
            JS::set('token', Session::getInstance()->getToken());
            JS::set('cms.cms_' . $name . '.config', !empty($settings['config']) ? $settings['config'] : []);
            return
                '<a href="javascript:lightning.cms.edit(\'cms_' . $name . '\')" class="button small" id="cms_edit_' . $name . '">Edit</a>'
                . '<a href="javascript:lightning.cms.save(\'cms_' . $name . '\')" class="button small" style="display:none;" id="cms_save_' . $name . '">Save</a>'
                . HTMLEditor::div('cms_' . $name,
                    array(
                        'spellcheck' => true,
                        'content' => $content,
                        'browser' => true,
                        'edit_border' => !empty($settings['edit_border']),
                        'config' => !empty($settings['config']) ? $settings['config'] : [],
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
        $content = CMSModel::loadByName($name);
        if (empty($content)) {
            $content = (object) [
                'class' => '',
                'content' => !empty($settings['default']) ? $settings['default'] : '',
                'url' => !empty($settings['defaultUrl']) ? $settings['defaultUrl'] : '',
            ];
        }
        if (!empty($content->content) && empty($content->url)) {
            // Needs a file prefix for rendering.
            $handler = FileManager::getFileHandler(!empty($settings['file_handler']) ? $settings['file_handler'] : '', $settings['location']);
            $content->url = $handler->getWebURL($content->content);
        }
        $forced_classes = !empty($settings['class']) ? $settings['class'] : '';
        $added_classes = !empty($content->class) ? $content->class : '';
        if (!empty($settings['class'])) {
            $content->class .= ' ' . $settings['class'];
        }

        if (ClientUser::getInstance()->isAdmin()) {
            JS::set('token', Session::getInstance()->getToken());
            // TODO: This will need extra slashes if using the File handler.
            JS::set('cms.basepath', $settings['location']);
            if (empty($settings['file_handler'])) {
                $settings['file_handler'] = 'Lightning\Tools\IO\File';
            }
            $fh = FileManager::getFileHandler($settings['file_handler'], $settings['location']);
            JS::set('cms.baseUrl', $fh->getWebURL(''));
            JS::set('fileBrowser.type', Configuration::get('html_editor.browser'));
            JS::startup('lightning.cms.initImage();');
            return '<a href="" class="button small" onclick="javascript:lightning.cms.editImage(\'' . $name . '\'); return false;">Change</a>'
            . '<a href="" class="button small" onclick="javascript:lightning.cms.saveImage(\'' . $name . '\'); return false;">Save</a>'
            . '<input type="text" placeholder="classes" id="cms_' . $name . '_class" class="imagesCSS" name="' . $forced_classes . '" value="' . $added_classes . '" />'
            . '<img src="' . $content->url . '" id="cms_' . $name . '" class="' . $content->class . '" />';
        } else {
            if (!empty($content)) {
                return '<img src="' . $content->url . '" class="' . $content->class . '" />';
            }
        }
    }

    public static function plain($name, $settings = array()) {
        if ($content = CMSModel::loadByName($name)) {
            $value = $content->content;
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
}
