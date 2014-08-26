<?php

namespace Lightning\CLI;

class Database extends CLI {
    /**
     * Conform the schemas.
     */
    public function executeConformSchema() {
        foreach ($this->getList('Schema') as $class) {
            $schema = new $class();
            $schema->conformSchema();
        }
    }

    public function executeImportDefaults() {
        foreach ($this->getList('Content') as $class) {
            $schema = new $class();
            $schema->importContent();
        }
    }

    public function getList($type) {
        $list = array();
        $directories = array(
            'Lightning\\Database\\' . $type . '\\' => HOME_PATH . '/Lightning/Database/' . $type,
            'Source\\Database\\' . $type . '\\' => HOME_PATH . '/Source/Database/' . $type,
        );
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
