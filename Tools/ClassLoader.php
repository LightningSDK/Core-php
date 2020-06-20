<?php

namespace lightningsdk\core\Tools;

use Exception;

class ClassLoader {
    protected static $loaded = false;
    protected static $classes;
    protected static $classesRev;
    protected static $classLoader = [
        'lightningsdk\\core\\Tools\\Configuration' => 'lightningsdk\\core\\Tools\\Configuration',
        'lightningsdk\\core\\Tools\\Data' => 'lightningsdk\\core\\Tools\\Data',
    ];

    public static function reloadClasses() {
        self::$classes = Configuration::get('classes', []);
        self::$classesRev = array_flip(self::$classes);
    }

    /**
     * A custom class loader.
     *
     * @param string $classname
     */
    public static function classAutoloader($classname) {
        if (!self::$loaded) {
            // These are not overridable because they are required for startup.
            foreach (self::$classLoader as $class) {
                self::loadClassFile($class);
            }

            // Load the class definitions.
            self::$classes = Configuration::get('classes', []);
            self::$classesRev = array_flip(self::$classes);
            if (Configuration::isLoaded()) {
                self::$loaded = true;
            }
        }


        // If the class is explicitly set as an override.
        if (!empty(self::$classes[$classname])) {
            // Load the project version in the Source namespace.
            self::loadClassFile(self::$classes[$classname]);
            // Alias the Lightning namespace to the Source namespace.
            class_alias(self::$classes[$classname], $classname);
            return;
        }

        elseif (!empty(self::$classesRev[$classname])) {
            // Load the Lightning version in the Overridden namespace.
            // Check if a core class exists:
            if (self::loadClassFile(self::$classesRev[$classname] . 'Core')) {
                // since they tried to call the main override, we want to alias it with the actual override
                self::loadClassFile($classname);
                class_alias($classname, self::$classesRev[$classname], false);
                return;
            } else {
                // TODO: @deprecated - load the original with the override namespace
                self::classAutoloader(self::$classesRev[$classname]);
                return;
            }
        }

        // Load the class.
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
        // TODO: remove this when packages are updated
        $class_path = str_replace("_", "-", $class_path);
        foreach ([
                     HOME_PATH . DIRECTORY_SEPARATOR . $class_path . '.php',
                     HOME_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $class_path . '.php'
                 ] as $path) {
            if (file_exists($path)) {
                require_once $path;
                return true;
            }
        }
        return false;
    }
}
