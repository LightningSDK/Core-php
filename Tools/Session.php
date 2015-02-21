<?php

namespace Overridable\Lightning\Tools;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Logger;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Security\Random;
use Lightning\Tools\Singleton;

class Session extends Singleton {

    const STATE_REMEMBER = 1;
    const STATE_PASSWORD = 2;
    const STATE_APP = 4;

    const PRIMARY_KEY = 'session_id';

    protected $content = array();

    /**
     * Constructs the session object given it's row from the database, possibly with some
     * altered values from the instantiating static function.
     *
     * @param array $data
     */
    public function __construct($data = array()) {
        $this->data = $data;
        if (!empty($this->data['content'])) {
            $this->content = json_decode($this->data['content'], true);
        }
    }

    /**
     * Get the current session.
     *
     * @param boolean $create_object
     *   Whether to create a new object if it doesn't exist.
     * @param boolean $create_session
     *   Whether to create a new session for the client if one doesn't exit.
     *
     * @return Session
     *   The current session.
     */
    public static function getInstance($create_object = true, $create_session = true) {
        return parent::getInstance($create_object, $create_session);
    }

    protected static function loadRequestSessionKey() {
        return Request::cookie(Configuration::get('session.cookie'), 'hex');
    }

    /**
     * Create the session object.
     *
     * @param boolean $create_session
     *   Whether to create the session for the user.
     *
     * @return Session
     *   The current session.
     */
    public static function createInstance($create_session = true) {
        if ($session_key = static::loadRequestSessionKey()) {
            $session_criteria = array(
                'session_key' => array('LIKE', $session_key)
            );
            // If the session is only allowed on one IP.
            if (Configuration::get('session.single_ip')) {
                $session_criteria['session_ip'] = Request::server('ip_int');
            }

            // See if the session exists.
            if ($session_details = Database::getInstance()->selectRow('session', $session_criteria)) {
                // Load the session.
                $session = new static($session_details);

                // If the password time has lapsed, remove password status.
                if ($session->getState(Session::STATE_PASSWORD)) {
                    if ($session_details['last_ping'] < time() - Configuration::get('session.password_ttl')) {
                        if (!$session->unsetState(Session::STATE_PASSWORD)) {
                            // The session does not want to be remembered and has been destroyed.
                            return false;
                        }
                    } else {
                        $session->state |= static::STATE_PASSWORD;
                    }
                }

                $session->ping();
                return $session;
            } else {
                // Possible security issue.
                Logger::logIP('Bad session', Logger::SEVERITY_MED);
                // There is an old cookie that we should delete.
                // Send a cookie to erase the users cookie, in case this is really a minor error.
                static::clearCookie();
                return static::create();
            }
        }
        elseif ($create_session) {
            // No session exists, create a new one.
            return static::create();
        }
        else {
            return null;
        }
    }

    /**
     * Create a new session.
     *
     * @param int $user_id
     *   Optional user ID if the user is already known.
     * @param bool $remember
     *   Optional remember flag to remember the user after they have logged out.
     *
     * @return session
     */
    public static function create($user_id=0, $remember=false) {
        $session_details = array();
        $new_sess_key = static::getNewSessionId();
        $new_token = Random::getInstance()->get(64, Random::HEX);
        if (empty($new_sess_key) || empty($new_token)) {
            Messenger::error('Session error.');
        }
        $session_details['session_key'] = $new_sess_key;
        $session_details['last_ping'] = time();
        $session_details['session_ip'] = Request::server('ip_int');
        $session_details['user_id'] = $user_id;
        $session_details['state'] = 0 | ($remember ? static::STATE_REMEMBER : 0);
        $session_details['form_token'] = $new_token;
        $session_details['session_id'] = Database::getInstance()->insert('session', $session_details);
        $session = new static($session_details);
        $session->setCookie();
        return $session;
    }

    public static function reset($user_id = 0, $remember = false) {
        static::setInstance(static::create($user_id, $remember));
    }

    /**
     * Get the session token.
     *
     * @return string
     *   The token.
     */
    public function getToken() {
        return $this->form_token;
    }

    /**
     * Set the user to the session.
     *
     * @param $user_id
     *   The new user id.
     */
    public function setUser($user_id) {
        Database::getInstance()->update('session', array('user_id' => $user_id), array('session_id' => $this->id));
    }

    /**
     * Checks for password access.
     *
     * @param int $state
     * @return bool
     */
    public function getState($state) {
        return (($state & $this->state) == $state);
    }

    /**
     * This is called when the user enters their password and password access is now allowed.
     */
    public function setState($state) {
        $this->state = $this->state | $state;
        Database::getInstance()->update('session', ['state' => ['expression' => 'state | ' . $state]], ['session_id' => $this->id]);
    }

    /**
     * Drops the user out of the PIN approved state. This may still leave them with password access.
     */
    public function unsetState($state) {
        $this->state = $this->state & ~$state;
        Database::getInstance()->update('session', ['state' => ['expression' => 'state & ~ ' . $state]], ['session_id' => $this->id]);
    }

    /**
     * Destroy the current session and remove it from the database.
     */
    public function destroy () {
        if ($this->id) {
            Database::getInstance()->delete('session', array('session_id' => $this->id));
            $this->data = null;
        }
        $this->clearCookie();
    }

    /**
     * Update the last active time on the session.
     */
    public function ping() {
        // Make the cookie last longer in the database.
        Database::getInstance()->update('session', array('last_ping' => time()), array('session_id' => $this->id));
        // Make the cookie last longer in the browser.
        $this->setCookie();
    }

    /**
     * Output the cookie to the requesting web server (for relay to the client).
     */
    public function setCookie() {
        Output::setCookie(Configuration::get('session.cookie'), $this->session_key, Configuration::get('session.remember_ttl'), '/', Configuration::get('cookie_domain'));
    }

    /**
     * Sends a blank cookie to overwrite and forget any current session cookie.
     */
    static function clearCookie() {
        if (!headers_sent()) {
            unset($_COOKIE[Configuration::get('session.cookie')]);
            Output::setCookie(Configuration::get('session.cookie'), '', -1, '', Configuration::get('cookie_domain'));
        }
    }

    /**
     * Gets a new random unique session id.
     *
     * @return mixed
     */
    static function getNewSessionId() {
        do{
            $key = Random::getInstance()->get(64, Random::HEX);
            if (empty($key)) {
                return FALSE;
            }
        } while(Database::getInstance()->check('session', array('session_key'=>$key)));
        return $key;
    }

    /**
     * Dumps all sessions for the current user
     *
     * @param int $exception
     *   A session ID that can be left as active.
     */
    public function dump_sessions($exception=0) {
        // Delete this session.
        Database::getInstance()->delete('session',
            array(
                'user_id'=>$this->user_id,
                'remember'=>0,
                'session_id'=>array('!=', $exception)));
        // Clean users other sessions.
        Database::getInstance()->update(
            'session',
            array(
                'password_time' => 0,
                'pin_time' => 0,
            ),
            array(
                'user_id' => $this->user_id,
                'session_id' => array('!=', $exception),
            )
        );
    }

    /**
     * Remove all expired sessions from the database.
     *
     * @return integer
     *   The number of sessions removed.
     */
    public static function clearExpiredSessions() {
        $remember_ttl = Configuration::get('session.remember_ttl');
        if (empty($remember_ttl)) {
            return 0;
        }
        return Database::getInstance()->delete(
            'session',
            array(
                'last_ping' => array('<', time() - $remember_ttl)
            )
        );
    }

    /**
     * Issue a new random key to the session. Everything else stays the same.
     */
    public function scramble() {
        $new_sess_id = static::getNewSessionId();
        if (empty($new_sess_id)) {
            _die('Session error.');
        }
        Database::getInstance()->update('session', array('session_key'=>$new_sess_id), array('session_id'=>$this->id));
        $this->session_key = $new_sess_id;
        $this->setCookie();
    }


    public function destroy_all($user_id) {
        Database::getInstance()->delete('session', array('user_id'=>$user_id));
    }

    // Blank out the session cookie
    // No return value
    public function blank_session () {
        Output::clearCookie(Configuration::get('session.cookie'));
    }

    /**
     * Get the value of a saved session variable.
     *
     * @param string $field
     *   The name of the field.
     *
     * @return mixed
     *   The set value.
     */
    public function getSetting($field) {
        if (!empty($this->content[$field])) {
            return $this->content[$field];
        } else {
            return null;
        }
    }

    /**
     * Set a value for a setting.
     *
     * @param string $field
     *   The name of the field.
     * @param mixed $value
     *   The value for the field.
     */
    public function setSettings($field, $value) {
        $this->content[$field] = $value;
    }

    /**
     * Remove a setting from the session.
     *
     * @param string $field
     *   The field name
     */
    public function unsetSetting($field) {
        unset($this->content[$field]);
    }

    /**
     * Return the session content
     */
    public function getData () {
        return $this->content;
    }

    /**
     * Set session content
     *
     * @param $content
     *   Set all session fields.
     */
    public function setData ($content) {
        Database::getInstance()->update(
            'session',
            array('content' => json_encode($content)),
            array('session_id' => $this->id)
        );
    }

    /**
     * Save the current session data.
     */
    public function saveData () {
        Database::getInstance()->update(
            'session',
            array('content' => json_encode($this->content)),
            array('session_id' => $this->id)
        );
    }
}
