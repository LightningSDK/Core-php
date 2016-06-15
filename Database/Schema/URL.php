<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class URL extends Schema {
    const TABLE = 'url';

    public function getColumns() {
        return array(
            'url_id' => $this->autoincrement(),
            'url' => $this->varchar(255),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'url_id',
        );
    }
}
