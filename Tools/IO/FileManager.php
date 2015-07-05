<?php

namespace Lightning\Tools\IO;

class FileManager {
    public static function getFileHandler($handler, $location) {
        if (empty($handler)) {
            return new File($location);
        } else {
            return new $handler($location);
        }
    }
}
