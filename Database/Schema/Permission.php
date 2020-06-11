<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class Permission extends Schema {

    const TABLE = 'permission';

    public function getColumns() {
        return [
            'permission_id' => $this->autoincrement(),
            'name' => $this->varchar(255),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'permission_id',
        ];
    }
}
