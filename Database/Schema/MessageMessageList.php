<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class MessageMessageList extends Schema {

    const TABLE = 'message_message_list';

    public function getColumns() {
        return [
            'message_id' => $this->int(true),
            'message_list_id' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'primary' => [
                'columns' => ['message_id', 'message_list_id']
            ],
        ];
    }
}
