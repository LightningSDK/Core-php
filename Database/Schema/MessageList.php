<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class MessageList extends Schema {

    const TABLE = 'message_list';

    public function getColumns() {
        return array(
            'message_list_id' => $this->autoincrement(),
            'name' => $this->varchar(128),
            'visible' => $this->int(true, self::TINYINT),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'message_list_id',
        );
    }
}
