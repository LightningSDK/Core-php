<?php

namespace Lightning\Tools;

class DatabaseSchemaManager {
    /**
     * Conform the schemas.
     */
    public function execute() {
        $this->getSchemaList();
        foreach ($this->schema_list as $class) {
            $schema = new $class();
            $schema->conformSchema();
        }
    }

    public function getSchemaList() {
        $this->schema_list = array();
        $directories = array(
            'Lightning\\Schema\\' => HOME_PATH . '/Lightning/Schema',
            'Source\\Schema\\' => HOME_PATH . '/Source/Schema',
        );
        foreach ($directories as $path => $dir) {
            if (file_exists($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if (substr($file, 0, 1) == '.') {
                        continue;
                    }
                    $this->schema_list[] = $path . str_replace('.php', '', $file);
                }
            }
        }
    }
}
