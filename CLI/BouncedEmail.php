<?php

namespace Source\Controller;

use Lightning\Controller\IncomingMail;
use Lightning\Model\User;
use Lightning\Tools\Tracker;

class Bounce extends IncomingMail {
    public function execute() {
        $user_id = User::find_by_email($from_email);
        Tracker::trackEvent('Bounced Email', 0, $user_id);
    }
}
