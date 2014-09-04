<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class Tracker extends Schema {
    protected $table = 'tracker';

    public function getColumns() {
        return array(
            'tracker_id' => $this->autoincrement(),
            'tracker_name' => $this->varchar(64),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'tracker_id',
            'tracker_name' => array(
                'columns' => array('tracker_name'),
                'unique' => true,
                'size' => 5,
            ),
        );
    }
}
