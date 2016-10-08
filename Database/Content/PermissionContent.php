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
                'name' => 'edit_pages',
            ],
            [
                'permission_id' => 3,
                'name' => 'edit_blog',
            ],
            [
                'permission_id' => 4,
                'name' => 'edit_mail_messages',
            ],
            [
                'permission_id' => 5,
                'name' => 'send_mail_messages',
            ],
            [
                'permission_id' => 6,
                'name' => 'edit_users',
            ],
        ];
    }
}
