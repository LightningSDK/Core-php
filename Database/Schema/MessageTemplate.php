<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class MessageTemplate extends Schema {

    const TABLE = 'message_template';

    public function getColumns() {
        return array(
            'template_id' => $this->autoincrement(),
            'title' => $this->varchar(32),
            'subject' => $this->varchar(64),
            'body' => $this->text(Schema::MEDIUMTEXT),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'template_id',
        );
    }
}
