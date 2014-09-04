<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class MessageList extends Schema {

    protected $table = 'message_list';

    public function getColumns() {
        return array(
            'message_list_id' => $this->autoincrement(),
            'name' => $this->varchar(128),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'message_list_id',
        );
    }
}
