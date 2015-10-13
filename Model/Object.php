<?php

namespace Lightning\Model;

use Lightning\Tools\Database;

class Object {
    use ObjectDataStorage;

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
