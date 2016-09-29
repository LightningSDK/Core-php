<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class UserRole extends Schema {

    const TABLE = 'user_role';

    public function getColumns() {
        return [
            'user_id' => $this->int(true),
            'role_id' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'user_role' => [
                'columns' => ['user_id', 'role_id'],
                'unique' => true,
            ],
        ];
    }
}
