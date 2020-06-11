<?php
/**
 * @file
 * lightningsdk\core\Pages\Mailing\MessageLists
 */

namespace lightningsdk\core\Pages\Mailing;

use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Output;

/**
 * A page handler for editing bulk mailer messages.
 *
 * @package lightningsdk\core\Pages\Mailing
 */
class Lists extends Table {

    const TABLE = 'message_list';
    const PRIMARY_KEY = 'message_list_id';

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

    protected function initSettings() {
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
