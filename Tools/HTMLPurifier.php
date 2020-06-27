<?php

namespace lightningsdk\core\Tools;

use HTMLPurifier_Config;

class HTMLPurifier extends Singleton {
    /**
     * Get an HTMLPurifier instance.
     *
     * @return \HTMLPurifier
     *   The instance.
     */
    public static function createInstance() {
        return new \HTMLPurifier();
    }

    /**
     * Get a HTMLPurifier_Config instance.
     *
     * @return HTMLPurifier_Config
     *   The instance.
     */
    public static function getConfig() {
        $cacheDirectory = Configuration::get('hmtlpurifier.cache', 'cache');
        $cacheDirectory = File::absolute($cacheDirectory);
        if (!file_exists($cacheDirectory) && !mkdir($cacheDirectory, 0777, true) && !is_dir($cacheDirectory)) {
            throw new \RuntimeException(sprintf('HTML purifier directory "%s" can not be created', $cacheDirectory));
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.SerializerPath', $cacheDirectory);

        return $config;
    }
}
