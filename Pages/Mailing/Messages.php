<?php
/**
 * @file
 * Lightning\Pages\Mailing\Messages
 */

namespace Lightning\Pages\Mailing;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Output;

/**
 * A page handler for editing bulk mailer messages.
 *
 * @package Lightning\Pages\Mailing
 */
class Messages extends Table {
    /**
     * Require admin privileges.
     */
    public function __construct() {
        parent::__construct();
        if (ClientUser::getInstance()->details['type'] < 5) {
            Output::accessDenied();
        }
    }

    protected $table = 'message';
    protected $preset = array(
        'message_id' => array(
            'type' => 'hidden',
        ),
        'never_resend' => array(
            'type' => 'checkbox',
        ),
    );
    protected $action_fields = array(
        'send' => array(
            'type' => 'link',
            'url' => '/admin/mailing/send?id=',
            'display_value' => '<img src="/images/main/new_message.png" border="0">',
        ),
    );
}
