<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Pages\Table;

class Permissions extends Table {
    protected $table = 'permission';

    protected $search_fields = [
        'permission_id',
        'name',
    ];

    protected $searchable = true;
    protected $sort       = 'permission_id ASC';
    protected $rowClick   = ['type' => 'none'];

    protected function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    protected function initSettings() {

    }
}
