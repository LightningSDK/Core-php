<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class MessageTemplate extends Schema {

    const TABLE = 'message_template';

    public function getColumns() {
        return [
            'template_id' => $this->autoincrement(),
            'title' => $this->varchar(32),
            'subject' => $this->varchar(64),
            'body' => $this->text(Schema::MEDIUMTEXT),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'template_id',
        ];
    }
}
