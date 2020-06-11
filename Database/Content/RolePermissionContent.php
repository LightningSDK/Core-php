<?php

namespace lightningsdk\core\Database\Content;

use lightningsdk\core\Database\Content;

class RolePermissionContent extends Content {

    protected $table = 'role_permission';

    public function getContent() {
        return [
            [
                'role_id' => 1,
                'permission_id' => 1,
            ],
        ];
    }
}
