<?php
/**
 * @file
 * Lightning\Tools\ClientUser
 */

namespace Lightning\Tools;

use Lightning\Model\User;

/**
 * A singleton for the global user.
 *
 * @package Lightning\Tools.
 */
class ClientUser extends Singleton {

    /**
     * Get the currently logged in user.
     *
     * @return User
     *   The currently logged in user.
     */
    public static function getInstance() {
        return parent::getInstance();
    }

    /**
     * Create the default logged in user.
     *
     * @return User
     *   The currently logged in user.
     */
    public static function createInstance() {
        $session_id = Request::cookie(Configuration::get('session.cookie'), 'hex');
        $session_ip = Request::server('ip_int');
        // If a session is found.
        if ($session_id && $session_ip && $session = Session::load($session_id, $session_ip)) {
            // Try to load the user on this session.
            if ($user = User::loadBySession($session)) {
                return $user;
            }
        }

        // No user was found.
        return User::anonymous();
    }

    /**
     * Require the user to log in and return to this page afterwards.
     */
    public static function requireLogin() {
        if (self::getInstance()->id == 0) {
            Navigation::redirect('/user?redirect=' . Scrub::toURL(Request::get('request')));
        }
    }
}
