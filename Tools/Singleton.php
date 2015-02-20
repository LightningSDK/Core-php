<?php

namespace Lightning\Tools;

use Lightning\Bootstrap;
use Lightning\Model\Object;
use ReflectionClass;

/**
 * Class Singleton
 *
 * A base class for singleton tools.
 */
class Singleton extends Object {

    /**
     * A static instance of the singleton.
     *
     * @var Singleton
     */
    protected static $instances = array();

    /**
     * A list of overidden classes for reference.
     *
     * @var null
     */
    protected static $overrides = null;

    /**
     * Initialize or return an instance of the requested class.
     * @param boolean $create
     *   Whether to create the instance if it doesn't exist.
     *
     * @return Singleton
     */
    public static function getInstance($create = true) {
        $class = static::getStaticName();
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
        return call_user_func_array(array($class, 'createInstance'), $args);
    }

    protected static function createInstance() {
        return new static();
    }

    /**
     * Set the singleton instance.
     *
     * @param string $object
     *   The new instance.
     */
    public static function setInstance($object) {
        $class = static::getStaticName();
        self::$instances[$class] = $object;
    }

    /**
     * Create a new singleton.
     *
     * @return object
     *   The new instance.
     */
    public static function resetInstance() {
        $class = static::getStaticName();
        return self::$instances[$class] = self::getNewInstance($class);
    }

    protected static function getStaticName() {
        $class = get_called_class();
        $class = str_replace('Overridable\\', '', $class);
        if (!isset(self::$overrides)) {
            self::$overrides = array_flip(Configuration::get('classes', []));
        }
        return !empty(self::$overrides[$class]) ? self::$overrides[$class] : $class;
    }
}
