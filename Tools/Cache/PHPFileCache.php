<?php

namespace Lightning\Tools\Cache;

use Lightning\Tools\Configuration;

class PHPFileCache extends FileCache {
    public function __construct() {
        parent::__construct();
        $this->directory = Configuration::get('cache.php-file.path');
        $this->suffix = '.php';
    }

    public function read() {
        if (file_exists($this->fileName)) {
            try {
                include $this->fileName;
                return unserialize(base64_decode($val));
            } catch (\Exception $e) {
                throw new \Exception('Failed to load from cache', 0, $e);
            }
        }
        return null;
    }

    public function write() {
        // TODO: create database with TTL so this can be purged.
        file_put_contents($this->fileName, '<?php $val = "'.base64_encode(serialize($this->value)).'";');
    }
}
