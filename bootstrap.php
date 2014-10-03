<?php

use Lightning\Tools\Configuration;

// Set required global parameters.
if (!defined('HOME_PATH')) {
    define('HOME_PATH', __DIR__ . '/..');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', HOME_PATH . '/Source/Config');
}

/**
 * A custom class loader.
 *
 * @param string $classname
 */
function classAutoloader($classname) {
    if ($classname != 'Lightning\Tools\Configuration') {
        static $loaded = false;
        static $classes;
        static $overrides = array();
        static $overridable = array();
        if (!$loaded) {
            $classes = Configuration::get('classes');
            $overridable = Configuration::get('overridable');
            $loaded = true;
        }
        if (!empty($classes[$classname])) {
            // Load an override class and override it.
            $overridden_name = 'Overridden\\' . $classname;
            $overrides[$overridden_name] = $overridden_name;
            loadClassFile($classname);
            loadClassFile($classes[$classname]);
            class_alias($classes[$classname], $classname);
            return;
        }
        if (isset($overrides[$classname])) {
            return;
        }
        if (isset($overridable[$classname]) || isset($overridable['Overridable\\' . $classname])) {
            $class_file = str_replace('Overridable\\', '', $classname);
            loadClassFile($class_file);
            class_alias($overridable[$class_file], $class_file);
            return;
        }
    }
    loadClassFile($classname);
}

/**
 * Require the requested class file.
 *
 * @param $classname
 *   The name of the class.
 */
function loadClassFile($classname) {
    $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $classname);
    require_once HOME_PATH . DIRECTORY_SEPARATOR . $class_path . '.php';
}

// Set the autoloader to the Lightning autoloader.
spl_autoload_register('classAutoloader');
