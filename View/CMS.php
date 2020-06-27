<?php

namespace lightningsdk\core\View;

use lightningsdk\core\Model\Permissions;
use lightningsdk\core\Tools\Cache\Cache;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\IO\FileManager;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\Model\CMS as CMSModel;
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

    protected static $cache;
    protected static $cacheData = [];
    protected static $cacheDataOriginal;
    protected static $cacheKey = 'cms-content';
    protected static function initCache() {
        if (static::$cache == null) {
            static::$cache = Cache::get(Cache::PERMANENT);
            static::$cacheDataOriginal = static::$cacheData = static::$cache->get(static::$cacheKey) ?? [];
        }
    }
    protected static function loadWithCache($name, $settings, $default) {
        $user = ClientUser::getInstance(false);
        if (empty($user) || !$user->hasPermission(Permissions::EDIT_CMS)) {
            // In this case we can use the cache
            self::initCache();
            if (!empty($settings['cache']) && array_key_exists($name, static::$cacheData)) {
                // exists in cache
                return static::$cacheData[$name];
            }
        }

        if ($cms = CMSModel::loadByName($name)) {
            // loaded from db
            $value = $cms;
        } else {
            // default
            $value = new CMSModel($default);
        }

        if (!empty($settings['cache'])) {
            self::$cacheData[$name] = $value;
        }

        return $value;
    }

    public static function clearCache() {
        static::initCache();;
        static::$cache->unset(static::$cacheKey);
    }

    public function __destruct() {
        $user = ClientUser::getInstance(false);
        if (empty($user) || !$user->hasPermission(Permissions::EDIT_CMS)) {
            // We don't save cache for admins
            // TODO: This can be cleaned up
            if (json_encode(static::$cacheData) != json_encode(static::$cacheDataOriginal)) {
                static::$cache->set(static::$ccacheKey, static::$cacheData);
            }
        }
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
        $cms = self::loadWithCache($name, $settings, ['content' => $settings['default']??'']);
        $vars = [];
        if ($user_id = ClientUser::getInstance()->id) {
            $vars['USER_ID'] = $user_id;
        }
        $vars['WEB_ROOT'] = Configuration::get('web_root');
        if (!empty($settings['display_only'])) {
            return '<div>' . Markup::render($cms->content, $vars) . '</div>';
        } else if (ClientUser::getInstance()->hasPermission(Permissions::EDIT_CMS)) {
            JS::startup('lightning.cms.init()', ['document']);
            JS::set('token', FormTool::getToken());
            JS::set('cms.cms_' . $name . '.config', !empty($settings['config']) ? $settings['config'] : []);
            return
                '<img src="/images/lightning/pencil.png" class="cms_edit icon-16" id="cms_edit_' . $name . '">
            <img src="/images/lightning/save.png" class="cms_save icon-16" id="cms_save_' . $name . '" style="display:none">'
                . HTMLEditor::div('cms_' . $name,
                    [
                        'spellcheck' => true,
                        'content' => $cms->content,
                        'content_rendered' => Markup::render($cms->content, $vars),
                        'browser' => true,
                        'edit_border' => !empty($settings['edit_border']),
                        'config' => !empty($settings['config']) ? $settings['config'] : [],
                    ]
                );
        } else {
            return '<div>' . Markup::render($cms->content, $vars) . '</div>';
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
        $cms = self::loadWithCache($name, $settings, [
            'class' => '',
            'content' => $settings['default'] ?? '',
            'url' => $settings['defaultUrl'] ?? '',
        ]);
        $settings += self::$settings;
        if (!empty($cms->content) && empty($cms->url)) {
            // Needs a file prefix for rendering.
            $handler = FileManager::getFileHandler(!empty($settings['file_handler']) ? $settings['file_handler'] : '', $settings['location']);
            $cms->url = $handler->getWebURL($cms->content);
        }

        // These are classes that are always applied and not visible in the text field.
        $forced_classes = !empty($settings['class']) ? $settings['class'] : '';
        // These are added in the CMS text field.
        $added_classes = !empty($cms->class) ? $cms->class : '';
        if (!empty($settings['class'])) {
            $cms->class .= ' ' . $settings['class'];
        }

        if (!empty($settings['display_only'])) {
            return $cms->url;
        } elseif (ClientUser::getInstance()->hasPermission(Permissions::EDIT_CMS)) {
            JS::set('token', FormTool::getToken());
            // TODO: This will need extra slashes if using the File handler.
            JS::set('cms.basepath', $settings['location']);
            if (empty($settings['file_handler'])) {
                $settings['file_handler'] = 'lightningsdk\core\Tools\IO\File';
            }
            $fh = FileManager::getFileHandler($settings['file_handler'], $settings['location']);
            JS::set('cms.baseUrl', $fh->getWebURL(''));
            JS::set('fileBrowser.type', Configuration::get('html_editor.browser'));
            JS::startup('lightning.cms.init()', ['document']);
            if (!isset($settings['style'])) {
                $settings['style'] = [];
            }
            if (!empty($settings['norender'])) {
                $settings['style']['display'] = 'none';
            }
            return '<img src="/images/lightning/pencil.png" class="cms_edit_image icon-16" id="cms_edit_' . $name . '">
            <img src="/images/lightning/save.png" class="cms_save_image icon-16" id="cms_save_' . $name . '" style="display:none">'
            . '<input type="text" placeholder="classes" id="cms_' . $name . '_class" class="imagesCSS" name="' . $forced_classes . '" value="' . $added_classes . '" style="display:none" />'
            . '<img src="' . $cms->url . '" id="cms_' . $name . '" class="' . $cms->class . '" '
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
        $cms = self::loadWithCache($name, $settings, [
            'content' => $settings['default'] ?? '',
        ]);

        if (!empty($settings['display_only'])) {
            return $cms->content;
        } elseif (ClientUser::getInstance()->hasPermission(Permissions::EDIT_CMS)) {
            JS::startup('lightning.cms.init()', ['document']);
            JS::set('token', FormTool::getToken());
            $output = '<img src="/images/lightning/pencil.png" class="cms_edit_plain icon-16" id="cms_edit_' . $name . '">'
            . '<img src="/images/lightning/save.png" class="cms_save_plain icon-16" id="cms_save_' . $name . '" style="display:none">';
            if (!empty($settings['multi_line'])) {
                $output .= '<textarea id="cms_' . $name . '" style="display:none">' . $cms->content . '</textarea>';
            } else {
                $output .= '<input type="text" id="cms_' . $name . '" value="' . $cms->content . '" style="display:none" />';
            }
            $output .= '<span id="cms_display_' . $name . '">' . $cms->content . '</span>';
            return $output;
        } elseif (!empty($settings['norender'])) {
            return '';
        } else {
            return $cms->content;
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
        $cms = self::loadWithCache($name, $settings, [
            'content' => $settings['default'] ?? '',
        ]);

        $value = json_decode($cms->content, true);

        if (!empty($settings['display_only'])) {
            return implode(',', $value);
        } elseif (ClientUser::getInstance()->hasPermission(Permissions::EDIT_CMS)) {
            JS::startup('lightning.cms.init()', ['document']);
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
