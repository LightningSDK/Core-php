<?php

namespace Lightning\Pages\Mailing;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Template;

class ListUsers extends Table {

    protected $table = 'user';
    protected $key = 'user_id';

    protected $accessTable = 'message_list_user';
    protected $fields = array(
        'user_id' => array(),
        'email' => array(
            'type' => 'email',
        ),
        'last' => array(
            'type' => 'string',
        ),
        'first' => array(
            'type' => 'string',
        ),
    );

    protected $action_fields = array(
        'select' => array(
            'type' => 'checkbox',
            'display_name' => '',
        )
    );

    protected $rowClick = array(
        'type' => 'url',
        'url' => '/admin/users?id=',
    );

    protected $editable = false;
    protected $deleteable = false;

    public function __construct() {
        ClientUser::requireAdmin();

        $list_id = Request::get('list', 'int');
        if ($list_id === 0) {
            Template::getInstance()->set('title', 'Users not on any mailing list.');
            $this->accessTableWhere = array(
                'message_list_id' => array('IS NULL'),
            );
        } elseif ($list_id > 0) {
            $list = Database::getInstance()->selectField('name', 'message_list', array('message_list_id' => $list_id));
            Template::getInstance()->set('title', "Users on list {$list}.");
            $this->accessTableWhere = array(
                'message_list_id' => $list_id,
            );
        } else {
            Template::getInstance()->set('title', 'All users on all lists.');
        }

        parent::__construct();
    }
}
