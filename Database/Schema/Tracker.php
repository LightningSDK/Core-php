<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class Tracker extends Schema {
    const TABLE = 'tracker';

    public function getColumns() {
        return [
            'tracker_id' => $this->autoincrement(),
            'tracker_name' => $this->varchar(64),
            'category' => $this->varchar(32),
            'type' => $this->varchar(32),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'tracker_id',
            'tracker_name' => [
                'columns' => ['category', 'tracker_name'],
                'unique' => true,
            ],
        ];
    }
}
