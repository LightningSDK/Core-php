<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class UserTempKey extends Schema {

    const TABLE = 'user_temp_key';

    public function getColumns() {
        return [
            'user_id' => $this->int(true),
            'temp_key' => $this->char(44),
            'time' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'user_id',
        ];
    }
}
