<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;
use Lightning\Tools\Template;
use Lightning\View\Page;

class RolesDashboard extends Page {

    protected $page = 'admin/roles_dashboard';

    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    /**
     * Show the main page.
     */
    public function get() {
        $template = Template::getInstance();
        $template->set('admin_stats', false);
        $db = Database::getInstance();

        $template->set('roles', $db->selectAll(
            [
                'from' => 'role'
            ]
        ));

        $template->set('permissions', $db->selectAll(
            [
                'from' => 'permission'
            ]
        ));
    }

    /**
     * Upgrades users by assigning them roles at the base of their type field's value
     */
    public function postUpgradeRoles(){
        $users = Database::getInstance()->selectAll(
            [
                'from' => 'user',
                'join' => [
                    [
                        'LEFT JOIN',
                        'user_role',
                        'ON user_role.user_id = user.user_id'
                    ]
                ]
            ],
            [],
            ['user.user_id', 'user.type', 'user_role.role_id']
        );

        // because of we add new role, roles numbers are differ from user.types
        // so we use this array to make their conformity
        $typesToRoles = [
            '3' => 2, // View Images
            '4' => 3, // View Stats
            '5' => 1  // Admin
        ];

        // assigning roles
        $i = 0;
        foreach ( $users as $user ){
            // if role not set yet
            if ( empty ($user['role_id']) ){
                // insert
                if (array_key_exists($user['type'], $typesToRoles) ) {
                    if ( empty($user['role_id']) OR $user['role_id'] == NULL) {
                        $values = [
                            'role_id' => $typesToRoles[$user['type']],
                            'user_id' => $user['user_id']
                        ];
                        Database::getInstance()->insert('user_role', $values);
                        $i++;
                    }
                }
            }
        }

        Messenger::message(" $i users was upgraded!");
        return $this->get();
    }
}
