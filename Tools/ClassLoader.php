<?php

namespace Lightning\Tools;

use Exception;

class ClassLoader {
    protected static $loaded = false;
    protected static $classes;
    protected static $classesRev;
    protected static $classLoader = [
        'Lightning\\Tools\\Configuration' => 'Lightning\\Tools\\Configuration',
        'Lightning\\Tools\\Data' => 'Lightning\\Tools\\Data',
    ];

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
            // Load the Lightning version in the Overridden namespace.
            self::loadClassFile($classname);
            // Load the project version in the Source namespace.
            self::loadClassFile(self::$classes[$classname]);
            // Alias the Lightning namespace to the Source namespace.
            class_alias(self::$classes[$classname], $classname);
            return;
        }

        elseif (!empty(self::$classesRev[$classname])) {
            // Load the Lightning version in the Overridden namespace.
            self::classAutoloader(self::$classesRev[$classname]);
            return;
        }

        // Load the class.
        self::loadClassFile($classname);

        // If this was an overridable class, create the standard alias.
        if (!class_exists($classname, false) && class_exists($classname . 'Overridable', false)) {
            class_alias($classname . 'Overridable', $classname);
        }
        if (!trait_exists($classname, false) && trait_exists($classname . 'Overridable', false)) {
            class_alias($classname . 'Overridable', $classname);
        }
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
}
