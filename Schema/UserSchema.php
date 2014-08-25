<?php

namespace Lightning\Schema;

use Lightning\Tools\DatabaseSchema;

class UserSchema extends DatabaseSchema {

    protected $table = 'user';

    public function getColumns() {
        return array(
            'user_id' => $this->autoincrement(),
            'email' => $this->varchar(128),
            'password' => $this->char(64),
            'salt' => $this->char(64),
            'first' => $this->varchar(64),
            'last' => $this->varchar(64),
            'list_date' => $this->int(true),
            'register_date' => $this->int(true),
            'last_login' => $this->int(true),
            'last_active' => $this->int(true),
            'type' => $this->int(true, DatabaseSchema::TINYINT),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'user_id',
            'email' => array(
                'columns' => array('email'),
                'unique' => true,
            ),
        );
    }
}
