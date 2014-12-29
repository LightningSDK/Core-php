<?php
/**
 * @file
 * Contains Lightning\Pages\Track
 */

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Logger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Tracker;

/**
 * A page handler for the tracking image.
 *
 * @package Lightning\Pages
 */
class Track extends Page {
    protected function hasAccess() {
        return true;
    }

    /**
     * The main page handler, outputs a 1x1 pixel image.
     */
    public function get() {
        if ($t = Request::get('t', 'encrypted')) {
            // Track an encrypted link.
            if (!Tracker::trackLink($t)) {
                Logger::error('Failed to track encrypted link: ' . Encryption::aesDecrypt($t, Configuration::get('tracker.key')));
            }
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

    /**
     * Does not require encryption, uses token.
     */
    public function post() {
        $user = ClientUser::getInstance()->id;

        // TODO: These can be spoofed.
        // A verification method is needed.
        $tracker = Request::post('tracker');
        $sub = Request::post('id', 'int');

        // Track.
        Tracker::trackEvent($tracker, $sub, $user);

        Output::json(Output::SUCCESS);
    }
}
