<?php

namespace Lightning\Pages\Mailing;

use Lightning\Pages\Table;

class Messages extends Table {

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
