<?php

namespace Lightning\Tools\IO;

use Lightning\Tools\Configuration;

class FileManager {
    public static function getFileHandler($handler, $container) {
        $container = Configuration::get('imageBrowser.containers.' . $container);
        if (empty($handler)) {
            return new File($container['storage'], $container['url']);
        } else {
            return new $handler($container['storage'], $container['url']);
        }
    }
}
