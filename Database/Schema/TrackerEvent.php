<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class TrackerEvent extends Schema {
    const TABLE = 'tracker_event';

    public function getColumns() {
        return [
            'tracker_id' => $this->int(true),
            'user_id' => $this->int(true),
            'session_id' => $this->int(true),
            'sub_id' => $this->int(true),
            'date' => $this->int(true),
            'time' => $this->int(true),
        ];
    }

    public function getKeys() {
        return [
            'tracker_user' => [
                'columns' => ['tracker_id', 'user_id'],
            ],
        ];
    }
}
