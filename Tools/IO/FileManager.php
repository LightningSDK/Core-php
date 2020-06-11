<?php

namespace lightningsdk\core\Tools\IO;

use lightningsdk\core\Tools\Configuration;

class FileManager {
    public static function getFileHandler($handler, $container) {
        if (is_string($container)) {
            $container = Configuration::get('imageBrowser.containers.' . $container);
        }
        $storage = $container['storage'];
        $url = !empty($container['url']) ? $container['url'] : null;
        if (empty($handler)) {
            return new File($storage, $url);
        } else {
            return new $handler($storage, $url);
        }
    }
}
