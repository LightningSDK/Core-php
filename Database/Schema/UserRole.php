<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class UserRole extends Schema {

    protected $table = 'user_role';

    public function getColumns() {
        return array(
            'user_id' => $this->int(true),
            'role_id' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'user_role' => array(
                'columns' => array('user_id', 'role_id'),
                'unique' => true,
            ),
        );
    }
}
