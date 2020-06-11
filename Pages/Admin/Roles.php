<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Pages\Table;

class Roles extends Table {

    const TABLE = 'role';
    const PRIMARY_KEY = 'role_id';

    protected $search_fields = [
        'role_id',
        'name',
    ];

    protected $searchable = true;
    protected $sort       = 'role_id';
    protected $rowClick   = ['type' => 'none'];

    protected function hasAccess() {
        return ClientUser::requireAdmin();
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
