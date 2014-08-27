<?php

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Output;
use Lightning\Tools\Template;
use Lightning\View\TrackerHistory;

class Stats extends Page {
    public function __construct() {
        if (ClientUser::getInstance()->details['type'] < 5) {
            Output::accessDenied();
        }
    }

    public function get() {
        $template = Template::getInstance();
        $template->set('content', 'admin_mailing_stats');
        $tracker = new TrackerHistory('Mailing List');
        $tracker->render();
    }

    public function getData() {

    }
}
