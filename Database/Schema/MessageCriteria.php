<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class MessageCriteria extends Schema {

    protected $table = 'message_criteria';

    public function getColumns() {
        return array(
            'message_criteria_id' => $this->autoincrement(),
            'criteria_name' => $this->varchar(255),
        );
    }

    public function getKeys() {
        return [
            'primary' => 'message_criteria_id',
        ];
    }
}
