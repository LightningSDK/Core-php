<?php

namespace Lightning\Tools;

use HTMLPurifier_Config;

class HTMLPurifierWrapper extends Singleton {
    public function createInstance() {
        require_once HOME_PATH . '/Lightning/Vendor/htmlpurifier/library/HTMLPurifier.auto.php';
        return new \HTMLPurifier();
    }
}

class HTMLPurifierConfig extends Singleton {
    public function createDefault() {
        require_once HOME_PATH . '/Lightning/Vendor/htmlpurifier/library/HTMLPurifier/Config.php';
        return \HTMLPurifier_Config::createDefault();
    }
}
