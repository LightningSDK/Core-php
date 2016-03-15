<?php

namespace Lightning\Pages\Admin;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

class Events extends Table {
    protected $table = 'calendar';
    protected $key = 'event_id';

    protected $preset = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'time',
        'end_time' => 'time',
    ];

    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }
}