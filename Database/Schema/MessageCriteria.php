<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class MessageCriteria extends Schema {

    protected $table = 'message_criteria';

    public function getColumns() {
        return array(
            'message_criteria_id' => $this->autoincrement(),
            'criteria_name' => $this->varchar(255),
            'join' => $this->varchar(255),
            'where' => $this->varchar(255),
            'select' => $this->varchar(255),
            'group_by' => $this->varchar(255),
            'having' => $this->varchar(255),
        );
    }

    public function getKeys() {
        return [
            'primary' => 'message_criteria_id',
        ];
    }
}
