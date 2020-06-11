<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Pages\Table;

class Permissions extends Table {

    const TABLE = 'permission';
    const PRIMARY_KEY = 'permission_id';

    protected $search_fields = [
        'permission_id',
        'name',
    ];

    protected $searchable = true;
    protected $sort       = 'permission_id';
    protected $rowClick   = ['type' => 'none'];

    protected function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    protected function initSettings() {

    }
}
