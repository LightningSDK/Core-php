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
use Lightning\Model\Tracker;

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
        if ($t = Request::get('t', Request::TYPE_ENCRYPTED)) {
            // Track an encrypted link.
            if (!Tracker::trackByEncryptedLink($t)) {
                Logger::error('Failed to track encrypted link: ' . Encryption::aesDecrypt($t, Configuration::get('tracker.key')));
            }
        }
        elseif (Configuration::get('tracker.allow_unencrypted') && $tracker_id = Request::get('tracker', Request::TYPE_INT)) {
            // Track an unencrypted link.
            $user = Request::get('user', Request::TYPE_INT) ?: ClientUser::createInstance()->id;
            $sub = Request::get('sub', Request::TYPE_INT);
            Tracker::loadByID($tracker_id)->track($sub, $user);
        }

        // Output a single pixel image.
        Output::setContentType('image/png');
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
        $tracker_id = Request::post('tracker');
        $sub = Request::post('id', Request::TYPE_INT);

        // Track.
        Tracker::loadByID($tracker_id)->track($sub, $user);

        Output::json(Output::SUCCESS);
    }
}
