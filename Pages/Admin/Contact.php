<?php

namespace Lightning\Pages\Admin;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

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
    ];

    public function hasAccess() {
        return ClientUser::requireAdmin();
    }

    public function get() {

    }
}
