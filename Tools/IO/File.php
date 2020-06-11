<?php

namespace lightningsdk\core\Tools\IO;

class File implements FileHandlerInterface {

    protected $root;
    protected $web_root;
    protected $currentFile;
    protected $currentFileHandler;
    protected $file;

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
            $this->file = $file;
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
        // Make sure the directory exists.
        if (!file_exists(dirname($this->root . '/' . $file))) {
            mkdir(dirname($this->root . '/' . $file), 0660, true);
        }

        if (!empty($file) && $file != $this->file) {
            $this->file = $file;
            $this->currentFile = $file;
            if (!file_exists($this->root . '/' . $this->currentFile)) {
                touch($this->root . '/' . $this->currentFile);
            }
            $this->currentFileHandler = fopen($this->root . '/' . $this->currentFile, 'r+');
        }

        fseek($this->currentFileHandler, $offset);
        fwrite($this->currentFileHandler, $contents);
    }

    public function moveUploadedFile($file, $temp_file) {
        move_uploaded_file($temp_file, $this->getAbsoluteLocal($file));
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
        return $this->root . '/' . $file;
    }

}
