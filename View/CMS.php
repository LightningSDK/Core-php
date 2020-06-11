<?php

namespace lightningsdk\core\View;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\IO\FileManager;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\Model\CMS as CMSModel;
use lightningsdk\core\Tools\Session\BrowserSession;
use lightningsdk\core\View\HTMLEditor\HTMLEditor;
use lightningsdk\core\View\HTMLEditor\Markup;
use lightningsdk\core\Tools\Form as FormTool;

class CMS {

    protected static $settings;

    /**
     * All of these functions will return a CMS editor. The first parameter is always the name of the CMS,
     * which is the unique ID that will be used to store it's value. Two CMSs with the same name, even if
     * they have different types, will share the same value.
     *
     * The second options parameters may contain options specific to each CMS type, but will all include
     * the following:
     *
     * - norender boolean - If set to true, this will only be displayed for admin users.
     * - display_only - Outputs only the display value without the editing features that admins usually see.
     * - default - The default value if none exists in the database.
     */

    public static function renderMarkup($options) {
        if (empty($options['name'])) {
            return 'CMS Error: missing name';
        }

        if (empty($options['type'])) {
            $options['type'] = 'plain';
        }

        switch ($options['type']) {
            case 'plain':
                return static::plain($options['name'], $options);
            case 'embed':
                return static::embed($options['name'], $options);
            case 'image':
                return static::image($options['name'], $options);
        }

        return 'CMS Error: invalid type';
    }

    /**
     * Create an embedded html editor.
     *
     * @param $name
     * @param array $settings
     *
     * @return string
     */
    public static function embed($name, $settings = []) {
        // TODO: Add caching
        $content = CMSModel::loadByName($name);
        $content = (!empty($content) ? $content->content : (!empty($settings['default']) ? $settings['default'] : ''));
        $vars = [];
        if ($user_id = ClientUser::getInstance()->id) {
            $vars['USER_ID'] = $user_id;
        }
        $vars['WEB_ROOT'] = Configuration::get('web_root');
        if (ClientUser::getInstance()->isAdmin()) {
            JS::startup('lightning.cms.init()');
            JS::set('token', FormTool::getToken());
            JS::set('cms.cms_' . $name . '.config', !empty($settings['config']) ? $settings['config'] : []);
            return
                '<img src="/images/lightning/pencil.png" class="cms_edit icon-16" id="cms_edit_' . $name . '">
            <img src="/images/lightning/save.png" class="cms_save icon-16" id="cms_save_' . $name . '" style="display:none">'
                . HTMLEditor::div('cms_' . $name,
                    [
                        'spellcheck' => true,
                        'content' => $content,
                        'content_rendered' => Markup::render($content, $vars),
                        'browser' => true,
                        'edit_border' => !empty($settings['edit_border']),
                        'config' => !empty($settings['config']) ? $settings['config'] : [],
                    ]
                );
        } else {
            return '<div>' . Markup::render($content, $vars) . '</div>';
        }
    }

    public static function initSettings() {
        if (!isset(self::$settings)) {
            self::$settings = Configuration::get('cms', []) + [
                    'location' => 'images'
                ];
        }
    }

    public static function image($name, $settings = []) {
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

        // These are classes that are always applied and not visible in the text field.
        $forced_classes = !empty($settings['class']) ? $settings['class'] : '';
        // These are added in the CMS text field.
        $added_classes = !empty($content->class) ? $content->class : '';
        if (!empty($settings['class'])) {
            $content->class .= ' ' . $settings['class'];
        }

        if (!empty($settings['display_only'])) {
            return $content->url;
        } elseif (ClientUser::getInstance()->isAdmin()) {
            JS::set('token', FormTool::getToken());
            // TODO: This will need extra slashes if using the File handler.
            JS::set('cms.basepath', $settings['location']);
            if (empty($settings['file_handler'])) {
                $settings['file_handler'] = 'lightningsdk\core\Tools\IO\File';
            }
            $fh = FileManager::getFileHandler($settings['file_handler'], $settings['location']);
            JS::set('cms.baseUrl', $fh->getWebURL(''));
            JS::set('fileBrowser.type', Configuration::get('html_editor.browser'));
            JS::startup('lightning.cms.init()');
            if (!isset($settings['style'])) {
                $settings['style'] = [];
            }
            if (!empty($settings['norender'])) {
                $settings['style']['display'] = 'none';
            }
            return '<img src="/images/lightning/pencil.png" class="cms_edit_image icon-16" id="cms_edit_' . $name . '">
            <img src="/images/lightning/save.png" class="cms_save_image icon-16" id="cms_save_' . $name . '" style="display:none">'
            . '<input type="text" placeholder="classes" id="cms_' . $name . '_class" class="imagesCSS" name="' . $forced_classes . '" value="' . $added_classes . '" style="display:none" />'
            . '<img src="' . $content->url . '" id="cms_' . $name . '" class="' . $content->class . '" '
            . 'style="' . HTML::implodeStyles($settings['style']) . '"'
            . ' />';
        } elseif (!empty($settings['norender'])) {
            return '';
        } else {
            if (!empty($content)) {
                $output = '<img src="' . $content->url . '" class="' . $content->class . '" />';
                if (!empty($settings['link'])) {
                    $output = '<a href="' . Scrub::toHTML($settings['link']) . '">' . $output . '</a>';
                }
                return $output;
            }
        }
    }

    public static function plain($name, $settings = []) {
        if ($content = CMSModel::loadByName($name)) {
            $value = $content->content;
        } elseif (!empty($settings['default'])) {
            $value = $settings['default'];
        } else {
            $value = '';
        }

        if (!empty($settings['display_only'])) {
            return $value;
        } elseif (ClientUser::getInstance()->isAdmin()) {
            JS::startup('lightning.cms.init()');
            JS::set('token', FormTool::getToken());
            $output = '<img src="/images/lightning/pencil.png" class="cms_edit_plain icon-16" id="cms_edit_' . $name . '">'
            . '<img src="/images/lightning/save.png" class="cms_save_plain icon-16" id="cms_save_' . $name . '" style="display:none">';
            if (!empty($settings['multi_line'])) {
                $output .= '<textarea id="cms_' . $name . '" style="display:none">' . $value . '</textarea>';
            } else {
                $output .= '<input type="text" id="cms_' . $name . '" value="' . $value . '" style="display:none" />';
            }
            $output .= '<span id="cms_display_' . $name . '">' . $value . '</span>';
            return $output;
        } elseif (!empty($settings['norender'])) {
            return '';
        } else {
            return $value;
        }
    }

    /**
     * Render a color picker.
     *
     * @param string $name
     * @param array $settings
     *
     * @return string
     *   Default format is r,g,b[,a]
     *
     * TODO: This should incorporate an actual color picker editor.
     */
    public static function colorPicker($name, $settings = []) {
        if ($content = CMSModel::loadByName($name)) {
            $value = json_decode($content->content, true);
        } elseif (!empty($settings['default'])) {
            $value = $settings['default'];
        } else {
            $value = [];
        }
        $value = json_decode(json_encode($value), true);

        if (!empty($settings['display_only'])) {
            return implode(',', $value);
        } elseif (ClientUser::getInstance()->isAdmin()) {
            JS::startup('lightning.cms.init()');
            JS::set('token', FormTool::getToken());
            return '<img src="/images/lightning/pencil.png" class="cms_edit_plain icon-16" id="cms_edit_' . $name . '">'
            . '<img src="/images/lightning/save.png" class="cms_save_plain icon-16" id="cms_save_' . $name . '" style="display:none">'
            . '<input type="text" id="cms_' . $name . '" value="' . json_encode($value) . '" style="display:none" />'
            . '<span id="cms_display_' . $name . '">' . json_encode($value) . '</span>';
        } elseif (!empty($settings['norender'])) {
            return '';
        } else {
            return implode(',', $value);
        }
    }
}
