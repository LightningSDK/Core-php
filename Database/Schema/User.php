<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class User extends Schema {

    const TABLE = 'user';

    public function getColumns() {
        return [
            'user_id' => $this->autoincrement(),
            'email' => $this->varchar(128),
            'alt_email' => $this->varchar(128),
            'password' => $this->char(64),
            'salt' => $this->char(64),
            'first' => $this->varchar(64),
            'last' => $this->varchar(64),
            'timezone' => $this->varchar(32),
            'created' => $this->int(true),
            'registered' => $this->int(true),
            'last_login' => $this->int(true),
            'last_active' => $this->int(true),
            'referrer' => $this->int(true),
            'confirmed' => $this->int(true, Schema::TINYINT),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'user_id',
            'email' => [
                'columns' => ['email'],
                'unique' => true,
            ],
        ];
    }
}
