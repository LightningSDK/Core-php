<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class UserTag extends Schema {

    const TABLE = 'user_tag';

    public function getColumns() {
        return array(
            'tag_id' => $this->int(true),
            'name' => $this->varchar(64),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'tag_id',
        );
    }
}
