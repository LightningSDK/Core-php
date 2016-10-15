<?php

namespace Lightning\Database\Content;

use Lightning\Database\Content;

class PermissionContent extends Content {

    protected $table = 'permission';

    public function getContent() {
        return [
            [
                'permission_id' => 1,
                'name' => 'all',
            ],
            [
                'permission_id' => 2,
                'name' => 'edit pages',
            ],
            [
                'permission_id' => 3,
                'name' => 'edit blog',
            ],
            [
                'permission_id' => 4,
                'name' => 'edit mail messages',
            ],
            [
                'permission_id' => 5,
                'name' => 'send mail messages',
            ],
            [
                'permission_id' => 6,
                'name' => 'edit users',
            ],
        ];
    }
}
