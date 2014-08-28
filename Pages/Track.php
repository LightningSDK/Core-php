<?php
/**
 * @file
 * Contains Lightning\Pages\Track
 */

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Request;
use Lightning\Tools\Tracker;

/**
 * A page handler for the tracking image.
 *
 * @package Lightning\Pages
 */
class Track extends Page {
    /**
     * The main page handler, outputs a 1x1 pixel image.
     */
    public function get() {
        if ($t = Request::get('t', 'base64')) {
            // Track an encrypted link.
            Tracker::trackLink($t);
        }
        elseif (Configuration::get('tracker.allow_unencrypted') && $tracker = Request::get('tracker', 'int')) {
            // Track an unencrypted link.
            $user = Request::get('user', 'int') ?: ClientUser::createInstance()->id;
            $sub = Request::get('sub', 'int');
            Tracker::trackEventID($tracker, $sub, $user);
        }

        // Output a single pixel image.
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
        exit;
    }
}