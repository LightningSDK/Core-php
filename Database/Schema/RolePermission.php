<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class RolePermission extends Schema {

    const TABLE = 'role_permission';

    public function getColumns() {
        return [
            'role_id' => $this->int(true),
            'permission_id' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'role_permission' => [
                'columns' => ['role_id', 'permission_id'],
                'unique' => true,
            ],
            'permission' => [
                'columns' => ['permission_id'],
                'unique' => false,
            ]
        ];
    }
}
