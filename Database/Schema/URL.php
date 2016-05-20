<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class URL extends Schema {
    protected $table = 'url';

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
