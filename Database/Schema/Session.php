<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class Session extends Schema {

    const TABLE = 'session';

    public function getColumns() {
        return [
            'session_id' => $this->autoincrement(),
            'session_key' => $this->char(128),
            'session_ip' => $this->varchar(45),
            'last_ping' =>  $this->int(true),
            'user_id' => $this->int(true),
            'state' => $this->int(true, Schema::TINYINT),
            'form_token' => $this->char(128),
            'content' => $this->text(),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'session_id',
            'session_key' => [
                'columns' => ['session_key'],
                'unique' => true,
            ],
            'user_id' => [
                'columns' => 'user_id',
                'unique' => false,
            ],
        ];
    }
}
