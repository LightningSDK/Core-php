<?php

namespace Lightning\Model;

use stdClass;

trait ObjectDataStorage {
    /**
     * The data storage container.
     *
     * @var array
     */
    protected $__data = [];

    /**
     * To track changes in the data container.
     *
     * @var array
     */
    protected $__changed = [];

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
     *   ->{primary_key} (item inside ->data)
     *   ->{field_name} (item inside ->data)
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
     * Get the entire data contents of the object.
     *
     * @return array $data
     */
    public function getData() {
        return $this->__data;
    }

    /**
     * A setter function.
     *
     * This works for:
     *   ->id
     *   ->{primary_key} (item inside ->data)
     *   ->{field_name} (item inside ->data)
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
            default:
                $this->__changed[$var] = $var;
                $this->__data[$var] = $value;
                break;
        }
    }

    /**
     * Replace the entire data contents of the object.
     *
     * @param array $data
     */
    public function setData($data) {
        foreach ($data as $var => $value) {
            if (empty($this->__data[$var]) || $this->__data[$var] != $value) {
                $this->__changed[$var] = $var;
            }
        }
        $this->__data = $data;
    }

    /**
     * Convert JSON encoded fields to objects.
     */
    protected function initJSONEncodedFields() {
        foreach ($this->__json_encoded_fields as $field) {
            if (!empty($this->__data[$field])) {
                if (is_string($this->__data[$field])) {
                    $this->__json_encoded_source[$field] = $this->__data[$field];
                    $this->__data[$field] = json_decode($this->__data[$field]) ?: new stdClass();
                } elseif (is_array($this->__data[$field]) || is_object($this->__data[$field])) {
                    $this->__json_encoded_source[$field] = json_encode($this->__data[$field]);
                    $this->__data[$field] = json_decode($this->__json_encoded_source[$field]) ?: new stdClass();
                }
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
        $create_new = $this->__changed_all || empty($this->id) || $this->__createNew;
        if ($create_new) {
            $values = $this->__data;
        } else {
            $values = array();
            foreach ($this->__changed as $val) {
                $values[$val] = $this->__data[$val];
            }
        }

        foreach ($this->__json_encoded_fields as $field) {
            $encoded = json_encode($this->__data[$field]);
            if (!empty($encoded) && (
                    $create_new
                    || empty($this->__json_encoded_source[$field])
                    || $encoded != $this->__json_encoded_source[$field]
                )
            ) {
                $values[$field] = $encoded;
            }
        }

        return $values;
    }

    /**
     * Checks that the object is the same, including data and ID.
     *
     * @param Object $object
     *   The object to compare to.
     *
     * @return boolean
     */
    public function equals($object) {
        if ($this->__data != $object->getData()) {
            return false;
        }
        if (empty($this->id) || empty($object->id)) {
            return false;
        }
        return true;
    }

    /**
     * Checks that the object data is the same, excluding ID.
     *
     * @param Object $object
     *   The object to compare to.
     *
     * @return boolean
     */
    public function equalsData($object) {
        $data1 = $this->__data;
        $data2 = $object->getData();
        unset($data1[static::PRIMARY_KEY]);
        unset($data2[static::PRIMARY_KEY]);
        return $data1 == $data2;
    }
}
