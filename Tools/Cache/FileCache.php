<?php

namespace Lightning\Tools\Cache;

use Lightning\Tools\Configuration;

class FileCache extends BaseCache {

    protected $fileName;

    protected $reference;

    protected $ttl = INF;

    public function __construct() {
        $this->directory = Configuration::get('temp_dir');
        parent::__construct();
    }

    public function setName($name) {
        $this->name = $name;
        $this->reference = md5($name);
        $this->fileName = $this->directory . '/' . $this->reference . '.cache';
    }

    public function loadReference($reference) {
        $this->reference = $reference;
        $this->fileName = $this->directory . '/' . $this->reference . '.cache';
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

    public function clear($name) {
        unlink($this->getFileName($name));
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
