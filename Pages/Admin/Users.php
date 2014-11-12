<?php

namespace Lightning\Pages\Admin;

use Lightning\Model\User;
use Lightning\Pages\Table;
use Lightning\Tools\Request;

class Users extends Table {
    protected $table = 'user';
    protected $searchable = true;
    protected $search_fields = array('email', 'first', 'last');
    protected $preset = array(
        'password' => array(
            'type' => 'char',
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

    public function initSettings() {
        $this->preset['password']['submit_function'] = function(&$output) {
            if ($pass = Request::post('password')) {
                $salt = User::getSalt();
                $output['salt'] = bin2hex($salt);
                $output['password'] = User::passHash($pass, $salt);
            }
        };
        $this->preset['password']['edit_value'] = function(&$row) {
            return '';
        };
        $this->preset['password']['display_value'] = function(&$row) {
            return !empty($row['password']) ? 'Set' : '';
        };
    }
}
