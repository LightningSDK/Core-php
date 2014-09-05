<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Output;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Lightning\View\Page;
use Lightning\View\TrackerHistory;

/**
 * A page handler for viewing the page stats.
 *
 * @package Lightning\Pages
 */
class Stats extends Page {
    /**
     * Require admin privileges.
     */
    public function __construct() {
        parent::__construct();
        if (ClientUser::getInstance()->details['type'] < 5) {
            Output::accessDenied();
        }
    }

    /**
     * Show the main page.
     */
    public function get() {
        $template = Template::getInstance();
        $template->set('content', 'stats');
        $template->set('full_width', true);
        $tracker = new TrackerHistory('Mailing List');
        $tracker->render();
        JS::startup('lightning.multiplier.init();');
    }

    /**
     * Load requested data.
     */
    public function getData() {

    }
}
