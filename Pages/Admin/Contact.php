<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\ClientUser;

class Contact extends Table {

    const TABLE = 'user_contact';
    const PRIMARY_KEY = 'contact_id';

    protected $sort = ['time' => 'DESC'];
    protected $preset = [
        'time' => [
            'type' => 'datetime',
            'timezone' => 'user',
        ],
        'additional_fields' => 'json',
        'contact' => 'checkbox',
        'contact_sent' => 'checkbox',
        'user_message_sent' => 'checkbox',
        'spam' => 'checkbox',
    ];

    protected $accessControl = ['spam' => 0];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    public function get() {

    }
}
