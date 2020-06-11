<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class MessageList extends Schema {

    const TABLE = 'message_list';

    public function getColumns() {
        return [
            'message_list_id' => $this->autoincrement(),
            'name' => $this->varchar(128),
            'visible' => $this->int(true, self::TINYINT),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'message_list_id',
        ];
    }
}
