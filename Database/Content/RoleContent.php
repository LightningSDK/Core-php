<?php

namespace lightningsdk\core\Database\Content;

use lightningsdk\core\Database\Content;

class RoleContent extends Content {

    protected $table = 'role';

    public function getContent() {
        return [
            [
                'role_id' => 1,
                'name' => 'Admin',
            ],
        ];
    }
}
