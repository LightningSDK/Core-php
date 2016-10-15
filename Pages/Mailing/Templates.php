<?php

namespace Lightning\Pages\Mailing;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

class Templates extends Table {
    protected $table = 'message_template';
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
