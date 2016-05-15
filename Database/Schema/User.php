<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class User extends Schema {

    protected $table = 'user';

    public function getColumns() {
        return array(
            'user_id' => $this->autoincrement(),
            'email' => $this->varchar(128),
            'alt_email' => $this->varchar(128),
            'password' => $this->char(64),
            'salt' => $this->char(64),
            'first' => $this->varchar(64),
            'last' => $this->varchar(64),
            'created' => $this->int(true),
            'registered' => $this->int(true),
            'last_login' => $this->int(true),
            'last_active' => $this->int(true),
            'referrer' => $this->int(true),
            'confirmed' => $this->int(true, Schema::TINYINT),
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
