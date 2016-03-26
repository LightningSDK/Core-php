<?php

namespace Lightning\Tools\IO;

class File implements FileHandlerInterface {

    protected $root;

    public function __construct($root) {
        if ($root[0] == '/') {
            $this->root = $root;
        } else {
            $this->root = preg_replace('|/+|', '/', HOME_PATH . '/' . $root);
        }
    }

    public function exists($file) {
        return file_exists($this->root . '/' . $file);
    }

    public function read($file) {
        return file_get_contents($this->root . '/' . $file);
    }

    public function write($file, $contents) {
        if (!file_exists(dirname($this->root . '/' . $file))) {
            mkdir(dirname($this->root . '/' . $file), 0777, true);
        }
        file_put_contents($this->root . '/' . $file, $contents);
    }

    public function moveUploadedFile($file, $temp_file) {
        move_uploaded_file($temp_file, $this->root . '/' . $file);
    }

    public function delete($file) {
        unlink($this->root . '/' . $file);
    }

    public function getWebURL($file) {
        return '/' . $this->root . '/' . $file;
    }

    public function getAbsoluteLocal($file) {

    }

}
