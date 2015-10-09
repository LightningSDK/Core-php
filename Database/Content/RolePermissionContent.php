<?php

namespace Lightning\Database\Content;

use Lightning\Database\Content;

class RolePermissionContent extends Content {

    protected $table = 'role_permission';

    public function getContent() {
        return array(
            array(
                'role_id' => 1,
                'permission_id' => 1,
            ),
        );
    }
}
