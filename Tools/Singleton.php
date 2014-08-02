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
                static::$instances[$class] = $class::createInstance();
            } else {
                static::$instances[$class] = new $class();
            }
        }
        return static::$instances[$class];
    }
}
