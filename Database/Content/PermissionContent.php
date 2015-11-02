<?php

namespace Lightning\Database\Content;

use Lightning\Database\Content;

class PermissionContent extends Content {

    protected $table = 'permission';

    public function getContent() {
        return array(
            array(
                'permission_id' => 1,
                'name' => 'all',
            ),
        );
    }
}
