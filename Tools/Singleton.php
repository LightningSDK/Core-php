<?php

namespace Lightning\Tools;

use Lightning\Bootstrap;
use ReflectionClass;

/**
 * Class Singleton
 *
 * A base class for singleton tools.
 */
class Singleton {

    /**
     * A static instance of the singleton.
     *
     * @var Singleton
     */
    protected static $instances = array();

    /**
     * Initialize or return an instance of the requested class.
     * @param boolean $create
     *   Whether to create the instance if it doesn't exist.
     *
     * @return Singleton
     */
    public static function getInstance($create = true) {
        $class = str_replace('Overridable\\', '', get_called_class());
        if (empty(static::$instances[$class]) && $create) {
            self::$instances[$class] = self::getNewInstance($class);
        }
        return !empty(self::$instances[$class]) ? self::$instances[$class] : null;
    }

    /**
     * Get the new instance by creating a new object of the inherited class.
     *
     * @param string
     *   The class to create.
     *
     * @return object
     *   The new instance.
     */
    private static function getNewInstance($class) {
        Bootstrap::classAutoloader($class);
        // There may be additional args passed to this function.
        $args = func_get_args();
        array_shift($args);
        if (is_callable($class . '::createInstance')) {
            return call_user_func_array(array($class, 'createInstance'), $args);
        } else {
            $reflect  = new ReflectionClass($class);
            return $reflect->newInstanceArgs($args);
        }
    }

    /**
     * Set the singleton instance.
     *
     * @param string $object
     *   The new instance.
     */
    public static function setInstance($object) {
        $class = str_replace('Overridable\\', '', get_called_class());
        self::$instances[$class] = $object;
    }

    /**
     * Create a new singleton.
     *
     * @return object
     *   The new instance.
     */
    public static function resetInstance() {
        $class = str_replace('Overridable\\', '', get_called_class());
        return self::$instances[$class] = self::getNewInstance($class);
    }
}
