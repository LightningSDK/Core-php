<?php

namespace Lightning;

// Set required global parameters.
if (!defined('HOME_PATH')) {
    define('HOME_PATH', __DIR__ . '/..');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', HOME_PATH . '/Source/Config');
}

use Lightning\Tools\Configuration;
use Lightning\Tools\Logger;

// Set the autoloader to the Lightning autoloader.
spl_autoload_register(array('\\Lightning\\Bootstrap', 'classAutoloader'));

// Set the error handler.
if (Configuration::get('errorlog') == 'stacktrace') {
    set_error_handler(array('\\Lightning\\Bootstrap', 'errorHandler'));
}

class Bootstrap {
    static $loaded = false;
    static $classes;
    static $overrides = array();
    static $overridable = array();
    static $loadedClasses = array();

    /**
     * A custom class loader.
     *
     * @param string $classname
     */
    public static function classAutoloader($classname) {
        if (empty(self::$loadedClasses[$classname]) && $classname != 'Lightning\Tools\Configuration') {
            if (!self::$loaded) {
                // Load the class definitions.
                self::$classes = Configuration::get('classes');
                self::$overridable = Configuration::get('overridable');
                self::$loaded = true;
            }
            if (!empty(self::$classes[$classname])) {
                // Load an override class and override it.
                $overridden_name = 'Overridden\\' . $classname;
                self::$overrides[$overridden_name] = $overridden_name;
                self::loadClassFile($classname);
                self::loadClassFile(self::$classes[$classname]);
                class_alias(self::$classes[$classname], $classname);
                self::$loadedClasses[$classname] = $classname;
                return;
            }
            if (isset(self::$overrides[$classname])) {
                return;
            }
            $class_file = str_replace('Overridable\\', '', $classname);
            if (isset(self::$overridable[$classname]) || isset(self::$overridable[$class_file])) {
                self::loadClassFile($class_file);
                self::$loadedClasses[$class_file] = $class_file;
                class_alias(self::$overridable[$class_file], $class_file);
                return;
            }
        }
        self::$loadedClasses[$classname] = $classname;
        self::loadClassFile($classname);
    }

    /**
     * Require the requested class file.
     *
     * @param string $classname
     *   The name of the class.
     */
    public static function loadClassFile($classname) {
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $classname);
        require_once HOME_PATH . DIRECTORY_SEPARATOR . $class_path . '.php';
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline) {
        Logger::errorLogStacktrace($errno, $errstr, $errfile, $errline);
    }
}
