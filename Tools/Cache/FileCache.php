<?php

namespace Lightning\Tools\Cache;

class FileCache extends BaseCache {

    protected $fileName;

    protected $ttl = INF;

    public function __construct() {
        $this->directory = HOME_PATH . '/cache';
        parent::__construct();
    }

    public function setName($name) {
        $this->name = $name;
        $this->fileName = $this->getFileName($name);
    }

    protected function getFileName($name) {
        return $this->directory . '/' . md5($name) . '.cache';
    }

    public function isValid() {
        return is_file($this->fileName) && filemtime($this->fileName) > time() - $this->ttl;
    }

    public function read() {
        return unserialize(file_get_contents($this->fileName));
    }

    public function write() {
        file_put_contents($this->fileName, serialize($this->value));
    }

    public function resetTTL() {
        touch($this->fileName);
    }

    public function clear($name) {
        unlink($this->getFileName($name));
    }

    public function clearAll() {
        $files = glob($this->directory . '/*.cache');
        foreach ($files as $f) {
            unlink ($f);
        }
    }
}
