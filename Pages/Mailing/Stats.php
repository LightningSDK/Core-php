<?php
/**
 * Lightning\Pages\Stats
 */

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Output;
use Lightning\Tools\Template;
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
        if (ClientUser::getInstance()->details['type'] < 5) {
            Output::accessDenied();
        }
    }

    /**
     * Show the main page.
     */
    public function get() {
        $template = Template::getInstance();
        $template->set('content', 'admin_mailing_stats');
        $tracker = new TrackerHistory('Mailing List');
        $tracker->render();
    }

    /**
     * Load requested data.
     */
    public function getData() {

    }
}
