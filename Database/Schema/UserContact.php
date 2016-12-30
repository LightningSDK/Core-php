<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class UserContact extends Schema {
    const TABLE = 'user_contact';

    public function getColumns() {
        return [
            'contact_id' => $this->autoincrement(),
            'user_id' => $this->int(true),
            'time' => $this->int(true),
            'contact' => $this->int(self::TINYINT),
            'contact_sent' => $this->int(self::TINYINT),
            'list_id' => $this->int(),
            'user_message' => $this->int(),
            'user_message_sent' => $this->int(self::TINYINT),
            'additional_fields' => $this->text(),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'contact_id',
        ];
    }
}
