<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Model\Permissions;
use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\ClientUser;

class Widgets extends Table {
    const TABLE = 'cms';
    const PRIMARY_KEY = 'cms_id';

    protected $preset = [
        'is_widget' => [
            'hidden' => true,
            'default' => 1,
            'force_default_new' => true,
        ],
        'content' => 'html',
        'last_modified' => [
            'type' => 'datetime',
            'editable' => false,
        ],
    ];

    protected $accessControl = ['is_widget' => 1];

    /**
     * @return boolean
     *
     * @throws \Exception
     */
    public function hasAccess() {
        return ClientUser::requirePermission(Permissions::EDIT_PAGES);
    }
}
