<?php

namespace lightningsdk\core\Model;

use lightningsdk\core\Tools\Data;

trait ObjectDotReference {
    /**
     * Get a config variable's value.
     *
     * @param string $variable
     *   The path to the variable within the config.
     *
     * @return mixed
     *   The value of the variable.
     */
    public function get($variable, $default = null) {
        return Data::getFromPath($variable, $this->__data[static::DOT_REFERENCE_FIELD], $default);
    }

    /**
     * Set a configuration variable's value.
     *
     * @param string $variable
     *   The name of the variable.
     *
     * @param mixed $value
     *   The new value.
     */
    public function set($variable, $value) {
        Data::setInPath($variable, $value, $this->__data[static::DOT_REFERENCE_FIELD]);
    }

    public function unset($variable) {
        Data::removeFromPath($variable, $this->__data[static::DOT_REFERENCE_FIELD]);
    }

    /**
     * Add a new value to an array.
     */
    public function push($path, $value) {
        Data::pushInPath($path, $value, $this->__data[static::DOT_REFERENCE_FIELD]);
    }

    /**
     * Merge new data into the configuration.
     *
     * @param array $new_data
     *   An array of new data to merge.
     */
    public function merge($new_data) {
        $this->__data[static::DOT_REFERENCE_FIELD] = array_replace_recursive($this->__data[static::DOT_REFERENCE_FIELD], $new_data);
    }

    /**
     * Merge new data into the configuration without replacing existing values.
     *
     * @param array $new_data
     *   An array of new data to merge.
     */
    public function softMerge($new_data) {
        $this->__data[static::DOT_REFERENCE_FIELD] = array_replace_recursive($new_data, $this->__data[static::DOT_REFERENCE_FIELD]);
    }

    public function data() {
        return $this->__data[static::DOT_REFERENCE_FIELD];
    }
}
