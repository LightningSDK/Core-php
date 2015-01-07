<?php

namespace Lightning\Tools;

use Lightning\Bootstrap;

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
            Bootstrap::classAutoloader($class);
            if (is_callable($class . '::createInstance')) {
                self::$instances[$class] = $class::createInstance();
            } else {
                self::$instances[$class] = new $class();
            }
        }
        return !empty(self::$instances[$class]) ? self::$instances[$class] : null;
    }

    /**
     * Set the singleton instance.
     *
     * @param $object
     *   The new instance.
     */
    public static function setInstance($object) {
        $class = str_replace('Overridable\\', '', get_called_class());
        self::$instances[$class] = $object;
    }
}
