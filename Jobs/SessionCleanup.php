<?php

namespace lightningsdk\core\Jobs;

use lightningsdk\core\Model\User;
use lightningsdk\core\Tools\Logger;
use lightningsdk\core\Tools\Session\DBSession;

class SessionCleanup extends Job {

    const NAME = 'Session Cleanup';

    public function execute($job) {
        // Remove expired sessions.
        Logger::info('Cleaning sessions...');
        $count = DBSession::clearExpiredSessions();
        Logger::info($count . ' sessions removed.');

        // Remove user reset keys.
        Logger::info('Cleaning expired user keys...');
        $count = User::removeExpiredTempKeys();
        Logger::info($count . ' user keys removed.');
    }
}
