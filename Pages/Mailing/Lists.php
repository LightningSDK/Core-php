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
    /**
     * Require admin privileges.
     */
    public function __construct() {
        ClientUser::requireAdmin();
        parent::__construct();
    }

    protected $table = 'message_list';
    protected $preset = array(
        'message_list_id' => array(
            'type' => 'hidden',
        ),
        'name' => array(
            'type' => 'string',
        ),
        'visible' => array(
            'type' => 'checkbox',
        )
    );

    protected $prefixRows = array(
        0 => array(
            'message_list_id' => 0,
            'name' => 'Unlisted',
        )
    );

    protected $rowClick = array(
        'type' => 'action',
        'action' => 'view',
    );

    protected function afterDelete($deleted_id) {
        // Clean up user connections for this list.
        Database::getInstance()->delete('message_list_user', array('message_list_id' => $deleted_id));
    }
}
