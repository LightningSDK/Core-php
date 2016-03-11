<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class TrackerEvent extends Schema {
    protected $table = 'url';

    public function getColumns() {
        return array(
            'url_id' => $this->int(true),
            'url' => $this->varchar(255),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'url_id',
        );
    }
}
