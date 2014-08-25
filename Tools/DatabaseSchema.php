<?php

namespace Lightning\Tools;

class DatabaseSchema {

    const INT = 'int';
    const TINYINT = 'tinyint';

    const TINYTEXT = 'tinytext';
    const TEXT = 'text';
    const MEDIUMTEXT = 'mediumtext';
    const LONGTEXT = 'longtext';

    public function loadSchema() {
//        $db = Database::getInstance();
//
//        if ($this->table_exists) {
//            $this->current_schema = $db->query();
//
//            $this->current_keys = array();
//            foreach ($db->query('Column_name',"SHOW KEYS FROM `{$this->table}`") as $key) {
//                $this->current_keys[$key['Key_name']] = $key;
//            }
//        }
    }

    public function conformSchema() {
        echo "Conforming {$this->table}:\n";
        $this->loadSchema();

        $db = Database::getInstance();
        $this->table_exists = $db->tableExists($this->table);

        if ($this->table_exists) {
//            foreach ($this->getKeys() as $key => $settings) {
//                if (!empty($this->current_keys[$key])) {
//                    if ($this->keysMatch($this->current_keys[$key])) {
//                        continue;
//                    }
//                    else {
//                        $db->dropIndex($this->table, $key);
//                    }
//                }
//                $db->query();
//            }
        }
        else {
            echo "Creating table:\n";
            $db->createTable($this->table, $this->getColumns(), $this->getKeys());
        }

        echo "Conforming complete.\n\n";
    }

    protected function varchar($size) {
        return array(
            'type' => 'varchar',
            'size' => $size,
        );
    }

    protected function char($size) {
        return array(
            'type' => 'char',
            'size' => $size,
        );
    }

    protected function text($size) {
        return array(
            'type' => $size,
        );
    }

    protected function int($unsigned = false, $size = self::INT) {
        return array(
            'type' => $size,
            'unsigned' => $unsigned,
        );
    }

    protected function autoincrement() {
        return array(
            'type' => 'int',
            'unsigned' => true,
            'auto_increment' => true,
        );
    }
}
