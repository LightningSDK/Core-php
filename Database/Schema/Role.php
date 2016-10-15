<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class Role extends Schema {

    const TABLE = 'role';

    public function getColumns() {
        return [
            'role_id' => $this->autoincrement(),
            'name' => $this->varchar(255),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'role_id',
        ];
    }
}
