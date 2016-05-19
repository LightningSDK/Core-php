<?php

namespace Lightning\Tools\Cache;

abstract class CacheController implements CacheControllerInterface {

    public $value;

    protected $original;

    protected $new = false;

    protected $name;

    protected $saveEmptyNew = true;

    protected $updateTTL = false;

    /**
     * Build the object and save the settings.
     *
     * @param array $settings
     */
    public function __construct($settings = []) {
        // Load any default with this class and save them.
        foreach ($settings as $setting => $value) {
            $this->$setting = $value;
        }
    }

    public function getClassDefaults() {}

    public function setName($name) {
        $this->name = $name;
    }

    public function isValid() {
        return false;
    }

    public function isNew() {
        return $this->new;
    }

    public function load($name, $default = null) {
        $this->setName($name);
        if ($this->isValid()) {
            $this->original = $this->value = $this->read();
        } else {
            $this->original = $this->value = $default;
            $this->new = true;
        }
    }

    public function __destruct() {
        if ($this->new && ($this->saveEmptyNew || !empty($this->value))) {
            // If this is new and empty with explicit save or new and not empty.
            $this->write();
        } elseif (serialize($this->value) != serialize($this->original)) {
            // If this was updated.
            $this->write();
        } elseif (!$this->saveEmptyNew && $this->updateTTL) {
            // It wasn't updated but we're supposed to update the ttl.
            $this->resetTTL();
        }
    }

    public function get($key, $default = null) {

    }

    public function set($key, $value) {

    }

    protected function read() {
        return null;
    }

    protected function write() {}

    protected function resetTTL() {}

    protected function clear($name) {}

    protected function clearAll() {}
}
