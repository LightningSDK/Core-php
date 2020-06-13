<?php

namespace lightningsdk\core\Tools;

use HTMLPurifier_Config;

class HTMLPurifierWrapper extends Singleton {
    /**
     * Get an HTMLPurifier instance.
     *
     * @return \HTMLPurifier
     *   The instance.
     */
    public static function createInstance() {
        return new \HTMLPurifier();
    }
}

class HTMLPurifierConfig extends Singleton {
    /**
     * Get a HTMLPurifier_Config instance.
     *
     * @return \Default|HTMLPurifier_Config
     *   The instance.
     */
    public static function createDefault() {
        return \HTMLPurifier_Config::createDefault();
    }
}
