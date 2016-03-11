<?php
/**
 * @file
 * Lightning\Pages\Mailing\MessageLists
 */

namespace Lightning\Pages\Mailing;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Output;

/**
 * A page handler for editing bulk mailer messages.
 *
 * @package Lightning\Pages\Mailing
 */
class Lists extends Table {

    protected $table = 'message_list';
    protected $preset = [
        'message_list_id' => [
            'type' => 'hidden',
        ],
        'name' => [
            'type' => 'string',
        ],
        'visible' => [
            'type' => 'checkbox',
        ],
    ];

    protected $prefixRows = [
        0 => [
            'message_list_id' => 0,
            'name' => 'Unlisted',
        ]
    ];

    protected $rowClick = [
        'type' => 'action',
        'action' => 'view',
    ];

    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    public function initSettings() {
        $this->preset['subscribers'] = [
            'list_only' => true,
            'display_value' => [$this, 'getListCount'],
        ];
    }

    public function getListCount($row) {
        return Database::getInstance()->countQuery([
            'from' => 'message_list_user',
            'where' => ['message_list_id' => $row['message_list_id']]
        ]);
    }

    protected function afterDelete($deleted_id) {
        // Clean up user connections for this list.
        Database::getInstance()->delete('message_list_user', ['message_list_id' => $deleted_id]);
    }
}
