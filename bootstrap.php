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
use Exception;

// Set the autoloader to the Lightning autoloader.
spl_autoload_register(array('\\Lightning\\Bootstrap', 'classAutoloader'));

// Set the error handler.
Logger::init();

class Bootstrap {
    protected static $loaded = false;
    protected static $classes;
    protected static $overrides = array();
    protected static $overridable = array();
    protected static $loadedClasses = array(
        'Lightning\\Tools\\Configuration' => 'Lightning\\Tools\\Configuration',
        'Lightning\\Tools\\Data' => 'Lightning\\Tools\\Data',
    );
    protected static $classLoader = array();

    /**
     * A custom class loader.
     *
     * @param string $classname
     */
    public static function classAutoloader($classname) {
        if (empty(self::$loadedClasses[$classname])) {
            // Make sure the configuration is loaded.
            if (!self::$loaded) {
                // Load the class definitions.
                self::$classes = Configuration::get('classes');
                self::$overridable = Configuration::get('overridable');
                self::$classLoader = Configuration::get('class_loader');
                self::$loaded = true;
            }

            // If the class is explicitly set as an override.
            if (!empty(self::$classes[$classname])) {
                // Load an override class and override it.
                $overridden_name = 'Overridden\\' . $classname;
                self::$overrides[$overridden_name] = $overridden_name;
                // Load the Lightning version in the Overridden namespace.
                self::loadClassFile($classname);
                // Load the overidden version in the Source namespace.
                self::loadClassFile(self::$classes[$classname]);
                // Alias the Lightning namespace to the Source namespace.
                class_alias(self::$classes[$classname], $classname);
                self::$loadedClasses[$classname] = $classname;
                return;
            }

            // If the class is already loaded as an override, do nothing.
            if (isset(self::$overrides[$classname])) {
                return;
            }

            // Load the overridable class.
            $class_file = str_replace('Overridable\\', '', $classname);
            if (isset(self::$overridable[$classname]) || isset(self::$overridable[$class_file])) {
                self::loadClassFile($class_file);
                self::$loadedClasses[$class_file] = $class_file;
                class_alias(self::$overridable[$class_file], $class_file);
                return;
            }
        }

        // No special tricks, just load the file.
        self::$loadedClasses[$classname] = $classname;
        self::loadClassFile($classname);
    }

    /**
     * Require the requested class file.
     *
     * @param string $classname
     *   The name of the class.
     *
     * @throws Exception
     *   If the class file can't be found.
     */
    public static function loadClassFile($classname) {
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $classname);
        if (file_exists(HOME_PATH . DIRECTORY_SEPARATOR . $class_path . '.php')) {
            require_once HOME_PATH . DIRECTORY_SEPARATOR . $class_path . '.php';
            return;
        }
        if (!empty(self::$classLoader['prefix'])) {
            foreach (self::$classLoader['prefix'] as $prefix => $directory) {
                $path_prefix = str_replace('\\', DIRECTORY_SEPARATOR, $prefix);
                if (preg_match('|^' . $path_prefix . '|', $class_path)) {
                    require_once preg_replace('|^' . $path_prefix . '|', HOME_PATH . DIRECTORY_SEPARATOR . $directory, $class_path) . '.php';
                    return;
                }
            }
        }
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline) {
        Logger::errorLogStacktrace($errno, $errstr, $errfile, $errline);
    }
}
