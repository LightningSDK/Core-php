<?php

namespace lightningsdk\core\Database;

use lightningsdk\core\Tools\Database;

abstract class Content implements ContentInterface{
    public function importContent() {
        echo "Importing data for table {$this->table}:\n";

        $db = Database::getInstance();

        foreach ($this->getContent() as $row) {
            $db->insert($this->table, $row, true);
        }

        echo "Importing complete.\n\n";
    }
}

interface ContentInterface {
    public function getContent();
}
