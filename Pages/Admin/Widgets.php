<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Model\Permissions;
use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\ClientUser;

class Widgets extends Table {
    const TABLE = 'widget';
    const PRIMARY_KEY = 'widget_id';

    protected $preset = [
        'content' => 'html',
        'last_modified' => [
            'type' => 'datetime',
            'editable' => false,
        ],
    ];

    /**
     * @return boolean
     *
     * @throws \Exception
     */
    public function hasAccess() {
        return ClientUser::requirePermission(Permissions::EDIT_PAGES);
    }
}
