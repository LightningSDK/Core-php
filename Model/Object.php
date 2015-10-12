<?php

namespace Lightning\Model;

use Lightning\Tools\Database;

class Object {
    use ObjectDataStorage {
        __isset as __parent__isset;
        __get as __parent__get;
        __set as __parent__set;
    }

    /**
     * The primary key form the database.
     */
    const PRIMARY_KEY = '';

    const TABLE = '';

    /**
     * Build an object from a data array.
     *
     * @param array $data
     */
    public function __construct($data = array()) {
        $this->__data = $data;
    }

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
        if ($var == 'id') {
            return !empty($this->__data[static::PRIMARY_KEY]);
        } else {
            return $this->__parent__isset($var);
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
        if ($var == 'id') {
            if (!empty($this->__data[static::PRIMARY_KEY])) {
                return $this->__data[static::PRIMARY_KEY];
            } else {
                return false;
            };
        } else {
            return $this->__parent__get($var);
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
        if ($var == 'id') {
            $this->__data[static::PRIMARY_KEY] = $value;
        } else {
            $this->__parent__set($var, $value);
        }
    }

    public static function loadAll($where = [], $fields = [], $final = '') {
        $objects = [];
        $results = Database::getInstance()->select(static::TABLE, $where, $fields, $final);
        foreach ($results as $row) {
            $objects[] = new static($row);
        }
        return $objects;
    }

    /**
     * Load a single element by the PK ID.
     *
     * @param integer $id
     *   The ID of the object.
     *
     * @return boolean|Object
     *   The new object
     */
    public static function loadByID($id) {
        if ($data = Database::getInstance()->selectRow(static::TABLE, [static::PRIMARY_KEY => $id])) {
            return new static($data);
        } else {
            return false;
        }
    }

    /**
     * Save any changed data.
     */
    public function save() {
        $db = static::getDatabase();

        if ($this->__changed_all || empty($this->id)) {
            $values = $this->__data;
        } else {
            $values = array();
            foreach ($this->__changed as $val) {
                $values[$val] = $this->__data[$val];
            }
        }

        if (empty($this->id)) {
            $this->id = $db->insert(static::TABLE, $values);
        } else {
            $db->update(static::TABLE, $values, [static::PRIMARY_KEY => $this->id]);
        }
    }

    public function delete() {
        static::getDatabase()->delete(static::TABLE, [static::PRIMARY_KEY => $this->id]);
    }

    /**
     * Get the database object associated with this object. This allows
     * an object to be overidden with a child object.
     *
     * @return Database
     *   The DB object.
     */
    public static function getDatabase() {
        return Database::getInstance();
    }
}
