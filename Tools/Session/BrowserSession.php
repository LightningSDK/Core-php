<?php

namespace Lightning\Tools\Session;

use Exception;
use Lightning\Model\ObjectDataStorage;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Security\Random;
use Lightning\Tools\SingletonObject;

/**
 * Class Session
 *   An object to reference a user's session on the site.
 *
 * @package Overridable\Lightning\Tools
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $last_ping
 * @property integer $state
 * @property string $session_key
 * @property string $form_token
 */
class BrowserSessionOverridable extends SingletonObject {

    use ObjectDataStorage;

    public static function createInstance() {
        if ($cookieVal = Request::cookie(self::cookieName(), Request::TYPE_ENCRYPTED)) {
            $data = json_decode(Encryption::aesDecrypt($cookieVal, Configuration::get('user.key')), true);
            return new static($data);
        } else {
            return new static([]);
        }
    }

    /**
     * Get the form token value
     *
     * @return string
     * @throws Exception
     */
    public function getFormToken() {
        if (empty($this->form_token)) {
            static::generateFormToken();
            $this->save();
        }

        return $this->form_token;
    }

    protected function generateFormToken() {
        $this->form_token = Random::getInstance()->get(64, Random::BASE64);
    }

    /**
     * Destroy the current session and remove it from the database.
     */
    public function destroy () {
        $this->__data = [];
        $this->clearCookie();
    }

    /**
     * Output the cookie to the requesting web server (for relay to the client).
     *
     * @throws Exception
     */
    public function save() {
        if (!empty($this->__data)) {
            $value = Encryption::aesEncrypt(
                json_encode($this->__data),
                Configuration::get('user.key')
            );
            Output::setCookie(
                static::cookieName(),
                $value,
                Configuration::get('session.remember_ttl'),
                '/',
                Configuration::get('cookie_domain')
            );
        }
    }

    /**
     * Sends a blank cookie to overwrite and forget any current session cookie.
     */
    protected static function clearCookie() {
        if (!headers_sent()) {
            unset($_COOKIE[self::cookieName()]);
            Output::clearCookie(self::cookieName());
        }
    }

    protected static function cookieName() {
        static $cookieName;
        if (empty($cookieName)) {
            $cookieName = Configuration::get('session.cookie') . 'd';
        }
        return $cookieName;
    }
}
