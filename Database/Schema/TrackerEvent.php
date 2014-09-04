<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class TrackerEvent extends Schema {
    protected $table = 'tracker_event';

    public function getColumns() {
        return array(
            'tracker_id' => $this->int(true),
            'user_id' => $this->int(true),
            'sub_id' => $this->int(true),
            'date' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'tracker_user' => array(
                'columns' => array('date', 'tracker_id'),
            ),
        );
    }
}
