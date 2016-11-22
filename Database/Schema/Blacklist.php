<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class Blacklist extends Schema {
    const TABLE = 'black_list';

    public function getColumns() {
        return [
            'black_list_id' => $this->autoincrement(),
            'time' => $this->int(true),
            'ip_start' => $this->varbinary(16),
            'ip_end' => $this->varbinary(16),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'blog_id',
            'ip_start' => [
                'columns' => ['ip_start'],
                'unique' => false,
            ],
        ];
    }
}
