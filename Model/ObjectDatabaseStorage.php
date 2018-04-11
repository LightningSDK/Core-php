<?php

namespace Lightning\Model;

use Lightning\Tools\Database;

trait ObjectDatabaseStorage {

    /**
     * Build an object from a data array.
     *
     * @param array $data
     */
    public function __construct($data = []) {
        $this->__data = $data;
        $this->initJSONEncodedFields();
    }

    /**
     * Load an array of objects.
     *
     * @param array $where
     *   A condition to be passed to the query.
     * @param array $fields
     * @param string $final
     * @param boolean $keyed
     *   Whether to key the list by the primary ID.
     *
     * @return array
     */
    public static function loadAll($where = [], $fields = [], $final = '', $keyed = false) {
        $objects = [];
        $results = Database::getInstance()->select(static::TABLE, $where, $fields, $final);

        $key = ($keyed === true) ? static::PRIMARY_KEY : $keyed;
        if (!empty($key)) {
            foreach ($results as $row) {
                $objects[$row[$key]] = new static($row);
            }
        } else {
            foreach ($results as $row) {
                $objects[] = new static($row);
            }
        }
        return $objects;
    }

    /**
     * Load objects using an array query.
     *
     * @param array $query
     *
     * @return array
     *   A list of objects found
     */
    public static function loadByQuery($query, $keyed = null) {
        $objects = [];
        $query = $query + ['from' => static::TABLE];
        $results = Database::getInstance()->queryArray($query);

        $key = ($keyed === true) ? static::PRIMARY_KEY : $keyed;
        foreach ($results as $result) {
            if (!empty($key)) {
                $objects[$result[$key]] = new static($result);
            } else {
                $objects[] = new static($result);
            }
        }

        return $objects;
    }

    /**
     * Select a list of available options with value of column $name_field, keyed by the primary key.
     *
     * @param string $name_field
     *   The field to use as values.
     * @param array $where
     *   A query filter.
     *
     * @return array
     *   A list of available options, keyed be the primary key.
     */
    public static function loadOptions($name_field, $where = []) {
        return Database::getInstance()->selectColumn(static::TABLE, $name_field, $where, static::PRIMARY_KEY);
    }

    /**
     * Load a single element by the PK ID.
     *
     * @param integer $id
     *   The ID of the object.
     *
     * @return static
     *   The new object
     */
    public static function loadByID($id) {
        if ($data = Database::getInstance()->selectRow(static::TABLE, [static::PRIMARY_KEY => $id])) {
            return new static($data);
        } else {
            return null;
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

    /**
     * Increment a field directly to the database.
     * 
     * @param string $field
     * @param integer $amount
     */
    public function increment($field, $amount = 1) {
        Database::getInstance()->update(static::TABLE,
            [$field => [
                'expression' => '`' . $field . '` + ?',
                'vars' => [$amount],
            ]],
            [static::PRIMARY_KEY => $this->__data[static::PRIMARY_KEY]]);
    }

    /**
     * Save a new object or update if it already exists.
     *
     * @param array $new_values
     *   An array of values for a new object.
     * @param array $update_values
     *   An array of values to update. This should exclude the primary key.
     *
     * @return integer
     *   The new ID
     */
    public static function insertOrUpdate($new_values, $update_values) {
        return Database::getInstance()->insert(static::TABLE,
            $new_values,
            $update_values
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
