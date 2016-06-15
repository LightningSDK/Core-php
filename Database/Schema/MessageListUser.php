<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class MessageListUser extends Schema {

    const TABLE = 'message_list_user';

    public function getColumns() {
        return array(
            'message_list_id' => $this->int(true),
            'user_id' => $this->int(true),
            'time' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'primary' => array(
                'columns' => array('message_list_id', 'user_id')
            ),
        );
    }
}
