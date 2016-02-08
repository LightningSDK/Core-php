<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class MessageMessageCriteria extends Schema {

    protected $table = 'message_message_criteria';

    public function getColumns() {
        return array(
            'message_id' => $this->int(true),
            'message_criteria_id' => $this->int(true),
        );
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