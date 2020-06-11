<?php
/**
 * @file
 * Contains lightningsdk\core\CLI\Database
 */

namespace lightningsdk\core\CLI;

/**
 * CLI controller for database functions.
 *
 * @package lightningsdk\core\CLI
 */
class Database extends CLI {
    /**
     * Conform the schemas by importing or updating from Database\Schema
     */
    public function executeConformSchema() {
        foreach ($this->getList('Schema') as $class) {
            $schema = new $class();
            $schema->conformSchema();
        }
    }

    /**
     * Imports default data in Database\Content
     */
    public function executeImportDefaults() {
        foreach ($this->getList('Content') as $class) {
            $schema = new $class();
            $schema->importContent();
        }
    }

    /**
     * Gets a list of schemas from the Schema or Content directory.
     *
     * @param string $type
     *   The type to load.
     *
     * @return array
     *   A list of classes in the directory.
     */
    public function getList($type) {
        $list = [];
        $directories = [
            'lightningsdk\\core\\Database\\' . $type . '\\' => HOME_PATH . '/Lightning/Database/' . $type,
            'Source\\Database\\' . $type . '\\' => HOME_PATH . '/Source/Database/' . $type,
        ];
        foreach ($directories as $path => $dir) {
            if (file_exists($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if (substr($file, 0, 1) == '.') {
                        continue;
                    }
                    $list[] = $path . str_replace('.php', '', $file);
                }
            }
        }
        return $list;
    }
}
