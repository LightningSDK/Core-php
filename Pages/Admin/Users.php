<?php

namespace Lightning\Pages\Admin;

use Lightning\Pages\Table;

class Users extends Table {
    protected $table = 'user';
    protected $preset = array(
        'password' => array(
            'type' => 'hidden',
        ),
        'salt' => array(
            'type' => 'hidden',
        ),
        'list_date' => array(
            'type' => 'datetime',
            'allow_blank' => true,
        ),
        'last_login' => array(
            'type' => 'datetime',
            'editable' => false,
        ),
        'active' => array(
            'type' => 'checkbox',
        ),
    );
    protected $links = array(
        'message_list' => array(
            'display_name' => 'Mailing Lists',
            'key' => 'message_list_id',
            'index' => 'message_list_user',
            'display_column' => 'name',
        ),
    );
}
