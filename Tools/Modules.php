<?php

namespace lightningsdk\core\Tools;

class Modules {

    protected static $modulePaths = ['Modules', 'vendor'];

    public static function initPage($module) {
        if (self::loadFile('View/Page.php', $module)) {
            $module = preg_replace('|/|', '\\', $module);
            call_user_func([$module . '\View\Page', 'init']);
        }
    }

    protected static function loadFile($file, $module) {
        foreach (self::$modulePaths as $path) {
            if (file_exists(HOME_PATH . '/' . $path . '/' . $module . '/' . $file)) {
                require_once HOME_PATH . '/' . $path . '/' . $module . '/' . $file;
                return true;
            }
        }
        return false;
    }

    public static function load($includeModules) {
        $config = [];
        for ($i = 0; $i < count($includeModules); $i++) {
            $module = $includeModules[$i];
            foreach (self::$modulePaths as $path) {
                if (file_exists(HOME_PATH . '/' . $path . '/' . $module . '/config.php')) {
                    $moduleConfig = require HOME_PATH . '/' . $path . '/' . $module . '/config.php';
                    $config = array_replace_recursive($moduleConfig, $config);
                    if (!empty($moduleConfig['modules']['include'])) {
                        foreach ($moduleConfig['modules']['include'] as $include) {
                            if (!in_array($include, $includeModules)) {
                                $includeModules[] = $include;
                            }
                        }
                    }
                }
            }
        }
        return $config;
    }
}
