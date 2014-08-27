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
     * @return User;
     */
    public static function getInstance() {
        return parent::getInstance();
    }

    /**
     * Create the default logged in user.
     */
    public static function createInstance() {
        $session_id = Request::cookie(Configuration::get('session.cookie'), 'hex');
        $session_ip = Request::server('ip_int');
        if ($session_id && $session_ip && $session = Session::load($session_id, $session_ip)) {
            return User::loadBySession($session);
        } else {
            return User::anonymous();
        }
    }
}
