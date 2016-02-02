<?php

namespace Lightning\Tools\SocialDrivers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Session;
use Lightning\View\JS;

class Twitter extends SocialMediaApi {

    const EMAIL_SUFFIX = 'twitter.com';

    /**
     * @var TwitterOAuth
     */
    public $connection;

    /**
     * @var
     */
    protected $me;

    public static function loadAutoLoader() {
        require_once HOME_PATH . '/Lightning/Vendor/twitterapiclient/autoload.php';
    }

    public static function createInstance($token = null) {
        if (empty($token)) {
            $token = Session::getInstance(true, false)->getSetting('twitter.token');
        }
        if (empty($token)) {
            return new static();
        }

        self::loadAutoLoader();

        $twitter = new static();
        $twitter->setToken($token);
        return $twitter;
    }

    public function setToken($token) {
        $appId = Configuration::get('social.twitter.key');
        $secret = Configuration::get('social.twitter.secret');

        $this->token = $token;
        $this->connection = new TwitterOAuth($appId, $secret, $token['oauth_token'], $token['oauth_token_secret']);
    }

    public function storeSessionData() {
        $session = Session::getInstance();
        $session->setSetting('twitter.token', $this->token);
        $session->saveData();
    }

    public static function getAccessToken() {
        self::loadAutoLoader();
        $session = Session::getInstance();

        $request_token = [
            'oauth_token' => $session->getSetting('twitter.oauth_token'),
            'oauth_token_secret' => $session->getSetting('twitter.oauth_token_secret'),
        ];
        if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) {
            // Abort! Something is wrong.
            Output::error('Invalid Request');
        }

        // Convert to access token.
        $key = Configuration::get('social.twitter.key');
        $secret = Configuration::get('social.twitter.secret');
        $connection = new TwitterOAuth($key, $secret, $request_token['oauth_token'], $request_token['oauth_token_secret']);
        $access_token = $connection->oauth('oauth/access_token', ['oauth_verifier' => $_REQUEST['oauth_verifier']]);
        return $access_token;
    }

    public function loadProfile() {
        if (empty($this->profile)) {
            $this->profile = $this->connection->get('account/verify_credentials');;
        }
    }

    public function authenticate() {
        $this->loadProfile();
        return !empty($this->profile);
    }

    public function getLightningUserData() {
        $this->loadProfile();
        $name = explode(' ', $this->profile->name, 2);
        $user_settings = [
            'first' => $name[0],
        ];
        if (count($name) == 2) {
            $user_settings['last'] = $name[1];
        }
        return $user_settings;
    }

    public function getSocialId() {
        return $this->profile->id;
    }

    public function myImageURL() {
        return $this->profile->profile_image_url;
    }

    public function myImageData() {
        return file_get_contents($this->myImageURL());
    }

    public function getFriends() {
        $friends = $this->connection->get('friends/ids');
        $followers = $this->connection->get('followers/ids');
        return array_unique(array_merge($friends->ids, $followers->ids));
    }

    public function getFriendIDs() {
        return $this->getFriends();
    }

    /**
     * Render the follow and tweet links.
     */
    public static function renderLinks() {
        $settings = Configuration::get('social.twitter');
        if (!empty($settings['follow']) || !empty($settings['share'])) {
            // Add the initialization script.
            JS::startup("!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');");

            $return = '';
            if (!empty($settings['share'])) {
                $via = !empty($settings['account']) ? ' data-via="' . $settings['account'] . '"' : '';
                $related = !empty($settings['share-hashtag']) ? ' data-related="' . $settings['share-hashtag'] . '"' : '';
                $return .= '<a href="https://twitter.com/share" class="twitter-share-button" data-count="none"' . $via . $related . ' >Tweet</a>';
            }
            if (!empty($settings['follow']) && !empty($settings['account'])) {
                $return .= '<a href="https://twitter.com/' . $settings['account'] . '" class="twitter-follow-button" data-show-count="false">Follow @' . $settings['account'] . '</a>';
            }
        }
        return $return;
    }

    public static function loginButton() {
        JS::set('token', Session::getInstance()->getToken());
        JS::startup('lightning.social.initLogin()');

        self::loadAutoLoader();
        $appId = Configuration::get('social.twitter.key');
        $secret = Configuration::get('social.twitter.secret');
        $connection = new TwitterOAuth($appId, $secret);
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => Configuration::get('web_root') . '/user/twitterauth'));

        // Save the token to the session.
        $session = Session::getInstance();
        $session->setSetting('twitter.oauth_token', $request_token['oauth_token']);
        $session->setSetting('twitter.oauth_token_secret', $request_token['oauth_token_secret']);
        $session->saveData();

        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        JS::set('social.twitter.signin_url', $url);
        return '<span class="social-signin twitter"><i class="fa fa-twitter"></i><span> Sign in with Twitter</span></span>';
    }
}
