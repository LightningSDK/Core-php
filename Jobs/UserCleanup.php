<?php

namespace Lightning\Jobs;

use Lightning\Model\User;
use Lightning\Tools\Logger;
use Lightning\Tools\Session\DBSession;

class UserCleanup extends Job {
    public function execute($job) {
        // Remove expired sessions.
        Logger::message('Cleaning sessions...');
        $count = DBSession::clearExpiredSessions();
        Logger::message($count . ' sessions removed.');

        // Remove user reset keys.
        Logger::message('Cleaning expired user keys...');
        $count = User::removeExpiredTempKeys();
        Logger::message($count . ' user keys removed.');
    }
}
