<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class UserUserTag extends Schema {

    const TABLE = 'user_user_tag';

    public function getColumns() {
        return array(
            'user_id' => $this->int(true),
            'tag_id' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'user_tag_tag' => array(
                'columns' => array('user_id', 'tag_id'),
                'unique' => true,
            ),
        );
    }
}
