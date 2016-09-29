<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Pages\Table;

class Roles extends Table {
    protected $table = 'role';


    protected $search_fields = [
        'role_id',
        'name',
    ];

    protected $searchable = true;
    protected $sort       = 'role_id ASC';
    protected $rowClick   = ['type' => 'none'];

    protected function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    protected function initSettings() {
        $this->links['permission'] = [
            'display_name'   => 'Permission',
            'key'            => 'permission_id',
            'index'          => 'role_permission',
            'display_column' => 'name',
        ];
    }
}
