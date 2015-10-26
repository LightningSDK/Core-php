<?php

namespace Lightning\CLI;

use Lightning\Model\User;
use Lightning\Tools\Database;
use Lightning\Tools\Tracker;

class BouncedEmail extends CLI {
    public function execute() {
        // Load the bounce handler.
        require_once HOME_PATH . '/Lightning/Vendor/BounceHandler/src/BounceHandler.php';
        $bounce_handler = new \cfortune\PHPBounceHandler\BounceHandler();

        // Parse the message.
        $bounce_info = $bounce_handler->get_the_facts(file_get_contents('php://stdin'));

        // If this was a message failure.
        if (!empty($bounce_info[0]['recipient']) && preg_match('/5\.\d\.\d/', $bounce_info[0]['status'])) {
            $email = $bounce_info[0]['recipient'];

            $user = User::loadByEmail($email);
            if (!$user) {
                // Bounced from an unknown recipient, ignore this.
                Tracker::trackEvent('Email Bounced', 0, 0);
                return;
            }

            // Track the bounced event.
            // TODO: we can scan the email for a link to see if we know the message id.
            Tracker::trackEvent('Email Bounced', 0, $user->user_id);

            // Get the last 6 send/bounce events.
            // TODO: Also check for a reactivation email.
            $mail_history = Database::getInstance()->select(
                'tracker_event',
                array(
                    'user_id' => $user->user_id,
                    'tracker_id' => array(
                        'IN',
                        array(
                            Tracker::getTrackerId('Email Sent'),
                            Tracker::getTrackerId('Email Bounced'),
                        ),
                    ),
                ),
                array(),
                'ORDER BY date DESC LIMIT 6'
            );

            $bounce_count = 0;
            $bounce_id = Tracker::getTrackerId('Email Bounced');
            foreach ($mail_history as $history) {
                if ($history['tracker_id'] == $bounce_id) {
                    $bounce_count++;
                }
            }

            // If there are two bounced messages, deactivate the user.
            if ($bounce_count >= 2) {
                // TODO: Instead of '1' here, we should have a table like `tracker`
                // that tracks tracker sub_ids by name.
                Tracker::trackEvent('Deactivate User', 1, $user->user_id);
                $user->unsubscribeAll();
            }
        }
    }
}
