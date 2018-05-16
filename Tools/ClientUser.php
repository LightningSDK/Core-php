<?php
/**
 * @file
 * Lightning\Tools\ClientUser
 */

namespace Lightning\Tools;

use Exception;
use Lightning\Model\User;
use Lightning\Tools\Session\BrowserSession;
use Lightning\Tools\Session\DBSession;

/**
 * A singleton for the global user.
 *
 * @package Lightning\Tools.
 */
class ClientUserOverridable extends Singleton {

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
        // If a session is found.
        $session = DBSession::getInstance(true, false);
        if ($session && $session->user_id > 0) {
            // If we are logged into someone elses account.
            if (!empty($session->content->impersonate)) {
                $user = User::loadById($session->content->impersonate);
            } else {
                // Try to load the user on this session.
                $user = User::loadById($session->user_id);
            }
        }

        if (!empty($user)) {
            $user->initSocialMediaApi();
            return $user;
        } else {
            // No user was found.
            return User::anonymous();
        }
    }

    /**
     * Require the user to log in and return to this page afterwards.
     *
     * @param string $action
     *   The action on the login page.
     *
     * @return boolean
     *   Returns true if the user is logged in.
     *
     * @throws Exception
     *   If the user is not logged in but this is a JSON request.
     */
    public static function requireLogin($action = '') {
        if (self::getInstance()->id == 0) {
            $query = [];
            if (!empty($action)) {
                $query['action'] = $action;
            }

            // Set the redirect parameter.
            $query['redirect'] = Request::getLocation();
            // Add the current query string.
            $redirect_query = $_GET;
            unset($redirect_query['request']);
            if (!empty($redirect_query)) {
                $query['redirect'] .= '?' . http_build_query($redirect_query);
            }
            if (Output::isJSONRequest()) {
                throw new Exception(Output::LOGIN_REQUIRED);
            } else {
                Navigation::redirect('/user' . $action, $query);
            }
            return false;
        } else {
            DBSession::impliedInitialization();
            return true;
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
        return true;
    }

    /**
     * Require to log in if not, and to have the supplied permission or give an access denied page.
     *
     * @param integer $permission
     *
     * @return boolean
     *
     * @throws Exception
     */
    public static function requirePermission($permission) {
        self::requireLogin();
        if (!self::getInstance()->hasPermission($permission)) {
            Output::accessDenied();
        }
        return true;
    }

    /**
     * @param bool $prioritizeSession
     * @return int|mixed
     */
    public static function getReferrer($prioritizeSession = false) {
        $session = BrowserSession::getInstance();

        // There is no session, there can't be a referrer.
        if ($prioritizeSession && !empty($session->content->referrer)) {
            return $session->referrer;
        }

        // If the user referrer is present.
        if ($clientUser = static::getInstance()) {
            // This is a known user with a referrer
            if (!empty($clientUser->referrer)) {
                return $clientUser->referrer;
            }
        }

        // If there is a session referrer.
        if (!empty($session->referrer)) {
            return $session->referrer;
        }

        return 0;
    }

    /**
     * If a referrer code is present, track it.
     */
    public static function trackReferrer() {
        if ($ref = Request::get('ref', Request::TYPE_INT)) {
            $session = BrowserSession::getInstance();
            $session->referrer = $ref;
            $session->save();
        }
    }
}
