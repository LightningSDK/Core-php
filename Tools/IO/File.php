<?php

namespace Lightning\Tools\IO;

class File {

    public function __construct($root) {
        $this->root = $root;
    }

    public function exists($file) {
        return file_exists(HOME_PATH . '/' . $this->root . '/' . $file);
    }

    public function read($file) {
        return file_get_contents(HOME_PATH . '/' . $this->root . '/' . $file);
    }

    public function write($file, $contents) {
        if (!file_exists(dirname(HOME_PATH . '/' . $this->root . '/' . $file))) {
            mkdir(dirname(HOME_PATH . '/' . $this->root . '/' . $file), 0777, true);
        }
        file_put_contents(HOME_PATH . '/' . $this->root . '/' . $file, $contents);
    }

    public function getWebURL($file) {
        return '/' . $this->root . '/' . $file;
    }

    public function getAbsoluteLocal($file) {

    }

}
