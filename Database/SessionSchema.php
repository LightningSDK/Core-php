<?php

namespace Lightning\Schema;

use Lightning\Tools\DatabaseSchema;

class SessionSchema extends DatabaseSchema {

    protected $table = 'session';

    public function getColumns() {
        return array(
            'session_id' => $this->autoincrement(),
            'session_key' => $this->char(128),
            'session_ip' => $this->int(true),
            'last_ping' =>  $this->int(true),
            'user_id' => $this->int(true),
            'state' => $this->int(true, DatabaseSchema::TINYINT),
            'form_token' => $this->char(128),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'session_id',
            'session_key' => array(
                'columns' => array('session_key'),
                'unique' => true,
            ),
            'user_id' => array(
                'columns' => 'user_id',
                'unique' => false,
            ),
        );
    }
}
