<?php

namespace lightningsdk\core\Tools\Cache;

use lightningsdk\core\Tools\Configuration;

class FileCache extends CacheController implements CacheControllerInterface {

    protected $fileName;

    protected $reference;

    protected $ttl = INF;

    public function __construct() {
        $this->directory = Configuration::get('temp_dir');
        $this->suffix = '.cache';
        parent::__construct();
    }

    public function get($key, $default = null) {
        $this->setName($key);
        return $this->read();
    }

    public function set($key, $value) {
        $this->setName($key);
        $this->value = $value;
        $this->write();
    }

    public function unset($key) {
        $this->setName($key);
        unlink($this->fileName);
    }

    public function setName($name) {
        $this->name = $name;
        $this->reference = hash("sha256", $name);
        $this->fileName = $this->directory . '/' . $this->reference . $this->suffix;
    }

    public function loadReference($reference) {
        $this->reference = $reference;
        $this->fileName = $this->directory . '/' . $this->reference . $this->suffix;
    }

    public function isValid() {
        return is_file($this->fileName) && filemtime($this->fileName) > time() - $this->ttl;
    }

    public function read() {
        return unserialize(file_get_contents($this->fileName));
    }

    public function write() {
        // TODO: create database with TTL so this can be purged.
        file_put_contents($this->fileName, serialize($this->value));
    }

    public function resetTTL() {
        touch($this->fileName);
    }

    public function clearAll() {
        $files = glob($this->directory . '/*.cache');
        foreach ($files as $f) {
            unlink ($f);
        }
    }

    /**
     * This is for more explicit caching methods from an uploaded file.
     *
     * @param $file
     * @param bool $uploaded
     */
    public function moveFile($file, $uploaded = true) {
        // TODO: create database with TTL so this can be purged.
        if ($uploaded) {
            move_uploaded_file($_FILES[$file]['tmp_name'], $this->fileName);
        } else {
            rename($file, $this->fileName);
        }
    }

    /**
     * This should only be used when direct access to the file is required,
     * like in CSV imports.
     *
     * @return string
     */
    public function getFile() {
        return $this->fileName;
    }

    public function getReference() {
        return $this->reference;
    }
}
