<?php

namespace Lightning\Model;

use stdClass;

trait ObjectDataStorage {
    /**
     * The data storage container.
     *
     * @var array
     */
    protected $__data = array();

    /**
     * To track changes in the data container.
     *
     * @var array
     */
    protected $__changed = array();

    /**
     * Track whether to update all fields.
     *
     * @var boolean
     */
    protected $__changed_all = false;

    /**
     * Track fields that will be JSON encoded.
     *
     * @var array
     */
    protected $__json_encoded_fields = [];
    protected $__json_encoded_source = [];

    /**
     * This will be set to true if an object is created with a specific ID.
     *
     * @var boolean
     */
    protected $__createNew = false;

    /**
     * Assist the getter function by checking for isset()
     *
     * @param string $var
     *   The name of the variable.
     *
     * @return boolean
     *   Whether the variable is set.
     */
    public function __isset($var) {
        switch($var) {
            case 'id':
                return !empty($this->__data[static::PRIMARY_KEY]);
                break;
            case 'data':
                return true;
                break;
            default:
                return isset($this->__data[$var]);
                break;
        }
    }

    /**
     * A getter function.
     *
     * This works for:
     *   ->id
     *   ->data
     *   ->{primary_key} (item inside ->data)
     *
     * @param string $var
     *   The name of the requested variable.
     *
     * @return mixed
     *   The variable value.
     */
    public function __get($var) {
        switch($var) {
            case 'id':
                if (!empty($this->__data[static::PRIMARY_KEY])) {
                    return $this->__data[static::PRIMARY_KEY];
                } else {
                    return false;
                };
                break;
            case 'data':
                return $this->__data;
                break;
            default:
                if (isset($this->__data[$var])) {
                    return $this->__data[$var];
                } else {
                    return NULL;
                }
                break;
        }
    }

    /**
     * A setter function.
     *
     * This works for:
     *   ->id
     *   ->data
     *   ->user_id (item inside ->data)
     *
     * @param string $var
     *   The name of the variable to set.
     * @param mixed $value
     *   The value to set.
     *
     * @return mixed
     *   The variable value.
     */
    public function __set($var, $value) {
        switch($var) {
            case 'id':
                $this->__createNew = true;
                $this->__changed[static::PRIMARY_KEY] = static::PRIMARY_KEY;
                $this->__data[static::PRIMARY_KEY] = $value;
                break;
            case 'data':
                $this->__changed_all = true;
                $this->__data = $value;
                break;
            default:
                $this->__changed[$var] = $var;
                $this->__data[$var] = $value;
                break;
        }
    }

    /**
     * Convert JSON encoded fields to objects.
     */
    protected function initJSONEncodedFields() {
        foreach ($this->__json_encoded_fields as $field) {
            if (!empty($this->__data[$field])) {
                $this->__json_encoded_source[$field] = $this->__data[$field];
                $this->__data[$field] = json_decode($this->__data[$field]) ?: new stdClass();
            } else {
                $this->__data[$field] = new stdClass();
            }
        }
    }

    /**
     * Get an array of modified fields for saving to the database.
     *
     * @return array
     */
    protected function getModifiedValues() {
        if ($this->__changed_all || empty($this->id)) {
            $values = $this->__data;
        } else {
            $values = array();
            foreach ($this->__changed as $val) {
                $values[$val] = $this->__data[$val];
            }
        }

        foreach ($this->__json_encoded_fields as $field) {
            $encoded = json_encode($this->__data[$field]);
            if (!empty($encoded) && (empty($this->__json_encoded_source[$field]) || $encoded != $this->__json_encoded_source[$field])) {
                $values[$field] = $encoded;
            }
        }

        return $values;
    }
}
