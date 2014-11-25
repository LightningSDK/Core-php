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
    public static function getInstance($create = true) {
        return parent::getInstance($create);
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
     *
     * @param string $action
     *   The action on the login page.
     */
    public static function requireLogin($action = '') {
        if (self::getInstance()->id == 0) {
            if (!empty($action)) {
                $action = 'action=' . Scrub::toURL($action) . '&';
            }
            Navigation::redirect('/user?' . $action . 'redirect=' . Scrub::toURL(Request::get('request')));
        }
    }

    /**
     * Require to log in if not, and to be an admin or give an access denied page.
     */
    public static function requireAdmin() {
        self::requireLogin();
        if (!self::getInstance()->isAdmin()) {
            Output::accessDenied();
        }
    }
}
