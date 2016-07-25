<?php

namespace Lightning\Tools\IO;

class File implements FileHandlerInterface {

    protected $root;
    protected $web_root;
    protected $currentFile;
    protected $currentFileHandler;

    public function __construct($root, $web_root = null) {
        if ($root[0] == '/') {
            $this->root = $root;
        } else {
            $this->root = preg_replace('|/+|', '/', HOME_PATH . '/' . $root);
        }
        if (!empty($web_root)) {
            $this->web_root = $web_root;
        } else {
            $this->web_root = preg_replace('|' . preg_quote(HOME_PATH) . '|', '', $this->root);
        }
    }

    public function exists($file) {
        return file_exists($this->root . '/' . $file);
    }

    public function read($file) {
        return file_get_contents($this->root . '/' . $file);
    }

    public function readRange($file, $start, $end) {
        if (!empty($file) && $file != $this->file) {
            $this->currentFile = $file;
            $this->currentFileHandler = fopen($this->root . '/' . $this->currentFile, 'r');
        }
        fseek($this->currentFileHandler, $start);
        return fread($this->currentFileHandler, 1 + $end - $start);
    }

    public function getSize($file) {
        return filesize($this->root . '/' . $file);
    }

    public function write($file, $contents, $offset = 0) {
        if (!file_exists(dirname($this->root . '/' . $file))) {
            mkdir(dirname($this->root . '/' . $file), 0777, true);
        }
        if ($offset == 0) {
            file_put_contents($this->root . '/' . $file, $contents);
        } else {
            $file = fopen($this->root . '/' . $file, 'w');
            fseek($file, $offset);
            fwrite($file, $contents);
        }
    }

    public function moveUploadedFile($file, $temp_file) {
        move_uploaded_file($temp_file, $this->root . '/' . $file);
    }

    public function delete($file) {
        unlink($this->root . '/' . $file);
    }

    public function getWebURL($file) {
        return $this->web_root . $file;
    }

    public function getFileFromWebURL($web_url) {
        return str_replace($this->web_root, '', $web_url);
    }

    public function getAbsoluteLocal($file) {

    }

}
