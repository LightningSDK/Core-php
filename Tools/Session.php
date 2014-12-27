<?php

namespace Overridable\Lightning\Tools;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Logger;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Security\Random;
use Lightning\Tools\Singleton;
use Lightning\View\JS;

class Session extends Singleton {
    // @todo these vars can probably be removed?
    var $id = 0;							// ROW ID in DB
    var $key;								// hex session key
    var $state = 0;
    var $user_id = 0;
    var $details;

    const STATE_REMEMBER = 1;
    const STATE_PASSWORD = 2;
    const STATE_APP = 4;

    /**
     * Constructs the session object given it's row from the database, possibly with some
     * altered values from the instantiating static function.
     *
     * @param array $session_details
     */
    private function __construct($session_details=array()){
        $this->id = $session_details['session_id'];
        $this->key = $session_details['session_key'];
        $this->user_id = $session_details['user_id'];
        $this->state = $session_details['state'];
        $this->user_id = $session_details['user_id'];
        $this->details = $session_details;
        $this->details['content'] = empty($this->details['content']) ? '' : json_decode($this->details['content'], true);
    }

    /**
     * Get the current session.
     *
     * @return Session
     *   The current session.
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
    }

    /**
     * Create the session object.
     *
     * @return Session
     *   The current session.
     */
    public static function createInstance() {
        if ($session_key = Request::cookie('session', 'hex')) {
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
                        $session->details['state'] |= static::STATE_PASSWORD;
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
        else {
            // No session exists, create a new one.
            return static::create();
        }
    }

    /**
     * Overrides magic get function.
     *
     * @param $var
     * @return mixed
     */
    public function __get($var){
        switch($var){
            case 'id':
                return $this->id;
                break;
            case 'details':
                return $this->details;
                break;
            default:
                if (isset($this->details[$var])) {
                    return $this->details[$var];
                } else {
                    return NULL;
                }
                break;
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
    public static function create($user_id=0, $remember=false){
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
        return $this->details['form_token'];
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
    public function getState($state){
        return (($state & $this->details['state']) == $state);
    }

    /**
     * This is called when the user enters their password and password access is now allowed.
     */
    public function setState($state){
        $this->details['state'] = $this->details['state'] | $state;
        Database::getInstance()->query("UPDATE session SET state = (state | " . $state . ") WHERE session_id={$this->id}");
    }

    /**
     * Drops the user out of the PIN approved state. This may still leave them with password access.
     */
    public function unsetState($state){
        $this->details['state'] = $this->details['state'] & ~$state;
        Database::getInstance()->query("UPDATE session SET state = (state & ~".$state.") WHERE session_id={$this->id}");
    }

    /**
     * Destroy the current session and remove it from the database.
     */
    public function destroy (){
        if($this->id) {
            Database::getInstance()->delete('session', array('session_id' => $this->id));
            $this->key = null;
            $this->id = null;
            $this->details = null;
            $this->state = null;
            $this->user_id = null;
        }
        $this->clearCookie();
    }

    /**
     * Update the last active time on the session.
     */
    public function ping(){
        // Make the cookie last longer in the database.
        Database::getInstance()->update('session', array('last_ping' => time()), array('session_id' => $this->id));
        // Make the cookie last longer in the browser.
        $this->setCookie();
    }

    /**
     * Output the cookie to the requesting web server (for relay to the client).
     */
    public function setCookie(){
        Output::setCookie(Configuration::get('session.cookie'), $this->key, Configuration::get('session.remember_ttl'), '/', Configuration::get('cookie_domain'));
    }

    /**
     * Sends a blank cookie to overwrite and forget any current session cookie.
     */
    static function clearCookie(){
        Output::setCookie(Configuration::get('session.cookie'), '', -1, '', Configuration::get('cookie_domain'));
    }

    /**
     * Gets a new random unique session id.
     *
     * @return mixed
     */
    static function getNewSessionId(){
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
    public function dump_sessions($exception=0){
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
    public function scramble(){
        $new_sess_id = static::getNewSessionId();
        if(empty($new_sess_id)) {
            _die('Session error.');
        }
        Database::getInstance()->update('session', array('session_key'=>$new_sess_id), array('session_id'=>$this->id));
        $this->key = $new_sess_id;
        $this->details['session_key'] = $new_sess_id;
        $this->setCookie();
    }


    public function destroy_all($user_id){
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
        if (!empty($this->details['content'][$field])) {
            return $this->details['content'][$field];
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
        $this->details['content'][$field] = $value;
    }

    /**
     * Remove a setting from the session.
     *
     * @param string $field
     *   The field name
     */
    public function unsetSetting($field) {
        unset($this->details['content'][$field]);
    }

    /**
     * Return the session content
     */
    public function getData () {
        return $this->details['content'];
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
            array('content' => json_encode($this->details['content'])),
            array('session_id' => $this->id)
        );
    }
}
