<?php

namespace Overridable\Lightning\Model;

use Exception;
use Lightning\Exceptions\TokenExpired;
use Lightning\Exceptions\TokenNotFound;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Tools\Singleton;
use Source\Overrides\Tools\Security\Random;

class Token extends Singleton {

    /**
     * Life time of token.
     */
    const EXPIRE_TIME = 600; //10 min

    const TABLE = 'action_token';
    const PRIMARY_KEY = 'token_id';

    /**
     * Additional data related and stored in this token.
     *
     * @var array
     */
    protected $token_data = array();

    /**
     * Set's the details of a token object.
     *
     * @param $data
     */
    public function __construct($data) {
        $this->data = $data;
        $this->token_data = !empty($data['token_data']) ? json_decode($data['token_data'], true) : [];
    }

    /**
     * Wrapper for getInstance()
     *
     * @return Token
     *   The token object.
     */
    public static function getInstance($create = true, $reset_time = false, $ignore_expiration = false, $new_if_not_found = false) {
        return call_user_func_array('parent::getInstance', func_get_args());
    }

    /**
     * Get the output to the client.
     * 
     * @return array
     */
    public function getOutput() {
        return $this->token_data + ['key' => $this->key];
    }

    /**
     * Create a new token. Creates random key and inserts into db.
     *
     * @return Token
     *   Token object.
     *
     * @throws Exception
     *   When something goes wrong.
     */
    static function create() {
        $time = time();
        // CREATE A NEW UNIQUE TOKEN
        $session = Session::getInstance();
        $user = ClientUser::getInstance();
        $db = Database::getInstance();
        do{
            $token = Random::get(32, Random::BASE64);
        } while ($db->check('action_token', array('key' => $token)));
        // MAKE SURE THERE IS A SESSION
        if (!is_object($session) || !$session->id) {
            $session = Session::getInstance();
        }
        // MAKE SURE WE HAVE A USER ID
        $user_id = $user->id;
        // INSERT THE TOKEN
        if ($id = $db->insert('action_token', array(
            'time' => $time,
            'user_id' => $user_id,
            'session_id' => $session->id,
            'key' => $token
        ))) {
            // SET MY DETAILS
            return new static(array(
                'token_id' => $id,
                'time' => $time,
                'user_id' => $user_id,
                'session_id' => $session->id,
                'key' => $token
            ));
        } else {
            throw new Exception('Could not create token.');
        }
    }

    /**
     * Load a token and it's data based on the current submission.
     *
     * @return token
     *   A token object.
     *
     * @throws Exception
     *   Token not found.
     *   Token expired and not renewable.
     */
    static function createInstance($reset_time = false, $ignore_expiration = false, $new_if_not_found = false) {
        $key = Request::post('token', 'base64');

        if (!empty($key) && $data = Database::getInstance()->selectRow('action_token', ['key' => $key])) {
            if ($data['time'] < time() - static::EXPIRE_TIME && !$ignore_expiration) {
                if ($new_if_not_found) {
                    return static::create();
                } else {
                    return static::handleExpiredToken($data);
                }
            } else {
                $token = new static($data);
                if ($reset_time) {
                    $token->updateTime();
                }
                return $token;
            }
        } elseif ($new_if_not_found) {
            return static::create();
        } else {
            throw new TokenNotFound('Token Not Found.');
        }
    }

    protected static function handleExpiredToken($data) {
        throw new TokenExpired('Token Expired.');
    }

    /**
     * Reset the expire time for this token.
     */
    public function updateTime() {
        $this->time = time();
        Database::getInstance()->update('action_token', ['time' => $this->time], ['key' => $this->key]);
    }

    /**
     * Write the token $data to the db.
     */
    public function saveData() {
        Database::getInstance()->update('action_token', ['token_data' => json_encode($this->token_data)], ['token_id' => $this->id]);
    }

    /**
     * Put some data in the token.
     *
     * @param $key
     *   The name of the variable.
     * @param $data
     *   The value of the variable.
     */
    function set($key, $data) {
        $this->token_data[$key] = $data;
    }

    /**
     * Gets data stored in this token.
     *
     * @param $key
     *   The name of the data.
     * @return mixed
     *   The data value.
     */
    function get($key) {
        if (!isset($this->token_data[$key])) {
            return null;
        } else {
            return $this->token_data[$key];
        }
    }

    public function getAll() {
        return $this->token_data;
    }

    /**
     * Removes all data from the token in and db.
     */
    function clear() {
        $this->data = array();
        $this->saveData();
    }

    /**
     * Kill the token so it can never be used again.
     */
    function destroy() {
        Database::getInstance()->delete('action_token', ['token_id' => $this->token_id]);
    }
}
