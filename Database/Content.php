<?php

namespace Lightning\Database;

use Lightning\Tools\Database;

class Content {
    public function importContent() {
        echo "Importing data for table {$this->table}:\n";

        $db = Database::getInstance();

        foreach ($this->getContent() as $row) {
            $db->insert($this->table, $row, true);
        }

        echo "Importing complete.\n\n";
    }
}
