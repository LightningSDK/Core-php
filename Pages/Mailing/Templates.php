<?php

namespace lightningsdk\core\Pages\Mailing;

use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\ClientUser;

class Templates extends Table {

    const TABLE = 'message_template';
    const PRIMARY_KEY = 'template_id';

    protected $preset = [
        'body' => [
            'type' => 'html',
            'full_page' => true,
            'editor' => 'full',
            'upload' => true,
            'url' => 'full',
        ]
    ];

    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }
}
