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
        require_once HOME_PATH . '/Lightning/Vendor/htmlpurifier/library/HTMLPurifier.auto.php';
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
        require_once HOME_PATH . '/Lightning/Vendor/htmlpurifier/library/HTMLPurifier/Config.php';
        return \HTMLPurifier_Config::createDefault();
    }
}
