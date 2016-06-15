<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class RolePermission extends Schema {

    const TABLE = 'role_permission';

    public function getColumns() {
        return array(
            'role_id' => $this->int(true),
            'permission_id' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'role_permission' => array(
                'columns' => array('role_id', 'permission_id'),
                'unique' => true,
            ),
            'permission' => array(
                'columns' => array('permission_id'),
                'unique' => false,
            )
        );
    }
}
