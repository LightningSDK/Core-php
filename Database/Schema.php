<?php

namespace lightningsdk\core\Database;

use lightningsdk\core\Tools\Database;

abstract class Schema implements SchemaInterface {

    const INT = 'int';
    const TINYINT = 'tinyint';
    const BIGINT = 'bigint';

    const TINYTEXT = 'tinytext';
    const TEXT = 'text';
    const MEDIUMTEXT = 'mediumtext';
    const LONGTEXT = 'longtext';

    const TABLE = '';

    protected $existing_columns;

    public function getKeys() {
        return [];
    }

    protected function hasColumn($column) {
        foreach ($this->existing_columns as $existing_column) {
            if ($existing_column['Field'] == $column) {
                return true;
            }
        }
        return false;
    }

    public function conformSchema() {
        echo "Conforming " . static::TABLE . ":\n";

        $db = Database::getInstance();
        $table_exists = $db->tableExists(static::TABLE);

        if ($table_exists) {
            $this->existing_columns = $db->query('SHOW COLUMNS FROM `' . static::TABLE . '`')->fetchAll();
            // Setting this to true will mean if the first column is added, it
            // will be the first column.
            $prev = true;
            // Update columns.
            foreach ($this->getColumns() as $column => $settings) {
                if (!$this->hasColumn($column)) {
                    $db->addColumn(static::TABLE, $column, $settings, $prev);
                }
                $prev = $column;
                // TODO: Remove deleted columns? What if they were renamed?
            }

            // Update Keys
            foreach ($this->getKeys() as $key => $settings) {
                // This doesn't work yet.
                return;
                if (!empty($this->current_keys[$key])) {
                    if ($this->keysMatch($this->current_keys[$key])) {
                        continue;
                    }
                    else {
                        $db->dropIndex(static::TABLE, $key);
                    }
                }
                $db->query();
            }
        }
        else {
            echo "Creating table:\n";
            $db->createTable(static::TABLE, $this->getColumns(), $this->getKeys());
        }

        echo "Conforming complete.\n\n";
    }

    protected function varchar($size) {
        return [
            'type' => 'varchar',
            'size' => $size,
        ];
    }

    protected function varbinary($size) {
        return [
            'type' => 'varbinary',
            'size' => $size,
        ];
    }

    protected function char($size) {
        return [
            'type' => 'char',
            'size' => $size,
        ];
    }

    protected function text($size = self::TEXT) {
        return [
            'type' => $size,
            'null' => true,
        ];
    }

    protected function int($unsigned = false, $size = self::INT) {
        return [
            'type' => $size,
            'unsigned' => $unsigned,
        ];
    }

    protected function autoincrement() {
        return [
            'type' => 'int',
            'unsigned' => true,
            'auto_increment' => true,
        ];
    }
}
