<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class MessageMessageCriteria extends Schema {

    const TABLE = 'message_message_criteria';

    public function getColumns() {
        return [
            'message_id' => $this->int(true),
            'message_criteria_id' => $this->int(true),
            'field_values' => $this->varchar(255),
        ];
    }

    public function getKeys() {
        return [
            'role_permission' => [
                'columns' => ['message_id', 'message_criteria_id'],
                'unique' => true,
            ],
        ];
    }
}
