<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class URL extends Schema {
    const TABLE = 'url';

    public function getColumns() {
        return [
            'url_id' => $this->autoincrement(),
            'url' => $this->varchar(255),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'url_id',
        ];
    }
}
