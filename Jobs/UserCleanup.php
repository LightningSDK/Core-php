<?php

namespace lightningsdk\core\Jobs;

use lightningsdk\core\Model\User;
use lightningsdk\core\Tools\Logger;
use lightningsdk\core\Tools\Session\DBSession;

class UserCleanup extends Job {

    const NAME = 'User Cleanup';

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
