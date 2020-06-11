<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class UserTag extends Schema {

    const TABLE = 'user_tag';

    public function getColumns() {
        return [
            'tag_id' => $this->int(true),
            'name' => $this->varchar(64),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'tag_id',
        ];
    }
}
