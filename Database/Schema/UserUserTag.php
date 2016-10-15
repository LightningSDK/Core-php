<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class UserUserTag extends Schema {

    const TABLE = 'user_user_tag';

    public function getColumns() {
        return [
            'user_id' => $this->int(true),
            'tag_id' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'user_tag_tag' => [
                'columns' => ['user_id', 'tag_id'],
                'unique' => true,
            ],
        ];
    }
}
