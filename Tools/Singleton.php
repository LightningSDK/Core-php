<?php

namespace Lightning\Tools;

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
     * @return Singleton
     */
    public static function getInstance() {
        $class = get_called_class();
        if (empty(static::$instances[$class])) {
            classAutoloader($class);
            if (in_array('createInstance', get_class_methods($class))) {
                self::$instances[$class] = $class::createInstance();
            } else {
                self::$instances[$class] = new $class();
            }
        }
        return self::$instances[$class];
    }

    /**
     * Set the singleton instance.
     *
     * @param $object
     *   The new instance.
     */
    public static function setInstance($object) {
        $class = get_called_class();
        self::$instances[$class] = $object;
    }
}
