<?php

namespace Lightning\Model;

use Lightning\Tools\Database;

class Object {
    use ObjectDataStorage;

    /**
     * The primary key form the database.
     */
    const PRIMARY_KEY = '';

    /**
     * The table where the object is stored.
     */
    const TABLE = '';

    /**
     * Build an object from a data array.
     *
     * @param array $data
     */
    public function __construct($data = array()) {
        $this->__data = $data;
        $this->initJSONEncodedFields();
    }

    /**
     * Load an array of objects.
     *
     * @param array $where
     * @param array $fields
     * @param string $final
     *
     * @return array
     */
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

        $values = $this->getModifiedValues();

        if (empty($values)) {
            return;
        }

        if ($this->__createNew) {
            // A new object was created with a hard coded ID.
            $db->insert(static::TABLE, $values);
        } elseif (empty($this->id)) {
            // A new object was created, PK will be created with auto increment.
            $this->__data[static::PRIMARY_KEY] = $db->insert(static::TABLE, $values);
        } else {
            // An existing object was loaded with a primary key.
            $db->update(static::TABLE, $values, [static::PRIMARY_KEY => $this->id]);
        }
    }

    public static function insertOrUpdate($update_values, $new_values) {
        return Database::getInstance()->insert(static::TABLE,
            $update_values,
            $new_values
        );
    }

    /**
     * Delete the object from the database.
     */
    public function delete() {
        if (empty($this->id)) {
            // The object was never saved.
            return;
        }
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
