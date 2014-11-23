<?php

namespace Lightning\Jobs;

use Lightning\CLI\CLI;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;

class Session extends CLI {
    public function execute() {
        $remember_ttl = Configuration::get('session.remember_ttl');
        Database::getInstance()->delete(
            'session',
            array(
                'last_ping' => array('<', time() - $remember_ttl)
            )
        );
    }
}
