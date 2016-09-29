<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

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
