<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Pages\Table;
use lightningsdk\core\Tools\ClientUser;

class Events extends Table {

    const TABLE = 'calendar';
    const PRIMARY_KEY = 'event_id';

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