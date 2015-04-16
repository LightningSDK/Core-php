<?php

namespace Lightning\Tools\SocialDrivers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\View\JS;

class Twitter extends SocialMediaApi {

    /**
     * @var TwitterOAuth
     */
    public $connection;

    /**
     * @var
     */
    protected $me;

    public static function loadAutoLoader() {
        require_once HOME_PATH . '/Source/Vendor/twitterapiclient/autoload.php';
    }

    public static function createInstance($token = null) {
        self::loadAutoLoader();
        $appId = Configuration::get('social.twitter.key');
        $secret = Configuration::get('social.twitter.secret');

        $twitter = new static();
        $twitter->connection = new TwitterOAuth($appId, $secret, $token['oauth_token'], $token['oauth_token_secret']);
        return $twitter;
    }

    public static function getAccessToken() {
        self::loadAutoLoader();
        session_start();
        $request_token['oauth_token'] = $_SESSION['oauth_token'];
        $request_token['oauth_token_secret'] = $_SESSION['oauth_token_secret'];

        if (isset($_REQUEST['oauth_token']) && $request_token['oauth_token'] !== $_REQUEST['oauth_token']) {
            // Abort! Something is wrong.
            Output::error('invalid request');
        }

        // Convert to access token (this should be done on the client side)
        $appId = Configuration::get('twitter.key');
        $secret = Configuration::get('twitter.secret');
        $connection = new TwitterOAuth($appId, $secret, $request_token['oauth_token'], $request_token['oauth_token_secret']);
        return $connection->oauth("oauth/access_token", array("oauth_verifier" => $_REQUEST['oauth_verifier']));
    }

    protected function loadProfile() {
        if (empty($this->me)) {
            $this->me = $this->connection->get("account/verify_credentials");;
        }
    }

    public function authenticate() {
        $this->loadProfile();
        return !empty($this->me);
    }

    public function getLightningUserData() {
        $this->loadProfile();
        $name = explode(' ', $this->me->name, 2);
        $user_settings = [
            'first' => $name[0],
        ];
        if (count($name) == 2) {
            $user_settings['last'] = $name[1];
        }
        return $user_settings;
    }

    public function getLightningEmail() {
        return $this->me->id . '@@twitter.com';
    }

    public function myImageURL() {
        return $this->me->profile_image_url;
    }

    public function myImageData() {
        return file_get_contents($this->myImageURL());
    }

    public function myFriends() {
//        return $this->service->people->listPeople('me', 'visible');
    }

    public static function loginButton() {
        self::loadAutoLoader();
        $appId = Configuration::get('twitter.key');
        $secret = Configuration::get('twitter.secret');
        $connection = new TwitterOAuth($appId, $secret);
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => 'http://test.concurrency.me/user/twitterauth'));
        $_SESSION['oauth_token'] = $request_token['oauth_token'];
        $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        return '<a href="' . $url . '">Sign in with twitter</a>"';
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
}
