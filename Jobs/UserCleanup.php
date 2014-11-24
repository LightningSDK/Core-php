<?php

namespace Lightning\Jobs;

use Lightning\CLI\CLI;
use Lightning\Model\User;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Logger;
use Lightning\Tools\Session;

class UserCleanup extends CLI {
    public function execute() {
        // Remove expired sessions.
        Logger::message('Cleaning sessions...');
        $count = Session::clearExpiredSessions();
        Logger::message($count . ' sessions removed.');

        // Remove user reset keys.
        Logger::message('Cleaning expired user keys...');
        $count = User::removeExpiredTempKeys();
        Logger::message($count . ' user keys removed.');
    }
}
