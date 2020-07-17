<?php

namespace lightningsdk\core\Model;

use lightningsdk\core\Tools\Database;

class PermissionsCore extends BaseObject {
    /**
     * Default permission for admins.
     */
    const ALL = 1;
    const EDIT_PAGES = 2;
    const EDIT_BLOG = 3;
    const EDIT_MAIL_MESSAGES = 4;
    const SEND_MAIL_MESSAGES = 5;
    const EDIT_USERS = 6;
    const EDIT_CMS = 7;

    const TABLE = 'permission';
    const PRIMARY_KEY = 'permission_id';

    protected $userid;
    protected $permissions;

    public static function loadByUserID($userid) {
        return new static($userid);
    }

    public function __construct($userid) {
        $this->userid = $userid;
        $this->loadPermissions();
    }

    protected function loadPermissions() {
        $this->permissions  = Database::getInstance()->selectColumnQuery([
            'from' => 'user',
            'join' => [
                [
                    'LEFT JOIN',
                    'user_role',
                    'ON user_role.user_id = user.user_id'
                ],
                [
                    'LEFT JOIN',
                    'role_permission',
                    'ON role_permission.role_id=user_role.role_id',
                ],
                [
                    'LEFT JOIN',
                    'permission',
                    'ON role_permission.permission_id=permission.permission_id',
                ],
                [
                    'JOIN',
                    'role',
                    'ON  user_role.role_id=role.role_id',
                ]
            ],
            'where' => [
                ['user.user_id' => $this->userid],
            ],
            'select' => ['permission.permission_id', 'permission.permission_id'],
        ]);
    }

    public function hasPermission($permissionID) {
        return !empty($this->permissions[$permissionID]) || !empty($this->permissions[static::ALL]);
    }
}
