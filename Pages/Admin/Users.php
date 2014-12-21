<?php

namespace Lightning\Pages\Admin;

use Lightning\Model\User;
use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Overridable\Lightning\Tools\Session;

class Users extends Table {

    public function __construct() {
        ClientUser::requireAdmin();
        parent::__construct();
    }

    protected $table = 'user';
    protected $searchable = true;
    protected $search_fields = array('email', 'first', 'last');
    protected $preset = array(
        'salt' => array(
            'type' => 'hidden',
        ),
        'last_login' => array(
            'type' => 'datetime',
            'editable' => false,
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

    protected $action_fields = array(
        'impersonate' => array(
            'type' => 'link',
            'url' => '/admin/users?action=impersonate&id=',
            'display_value' => 'Impersonate',
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

    public function getImpersonate() {
        $session = Session::getInstance();
        $session->setSettings('impersonate', Request::get('id', 'int'));
        $session->saveData();
        // TODO: This should call the User::loginRedirect() function.
        Navigation::redirect('/');
    }
}
