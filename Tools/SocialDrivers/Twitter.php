<?php

namespace Lightning\Tools\SocialDrivers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Session;
use Lightning\View\JS;
use stdClass;

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

    protected $network = 'twitter';

    public static function loadAutoLoader() {
        require_once HOME_PATH . '/Lightning/Vendor/twitterapiclient/autoload.php';
    }

    /**
     * @param null $token
     * @param boolean $authorize
     *   Not used, twitter is always authorized.
     *
     * @return static
     */
    public static function createInstance($token = null, $authorize = true) {
        if (empty($token)) {
            $session = Session::getInstance(true, false);
            if (!empty($session->content->twitter->token)) {
                $token = $session->content->twitter->token;
            }
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

    public function getToken() {
        return json_encode([
            'oauth_token' => $this->token['oauth_token'],
            'oauth_token_secret' => $this->token['oauth_token_secret']
        ]);
    }

    public function storeSessionData() {
        $session = Session::getInstance();
        if (empty($session->content->twitter)) {
            $session->content->twitter = new stdClass();
        }
        $session->content->twitter->token = $this->token;
        $session->save();
    }

    public static function getAccessToken() {
        self::loadAutoLoader();
        $session = Session::getInstance();

        $request_token = [
            'oauth_token' => !empty($session->content->twitter->oauth_token) ? $session->content->twitter->oauth_token : '',
            'oauth_token_secret' => !empty($session->content->twitter->oauth_token_secret) ? $session->content->twitter->oauth_token_secret : '',
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

    public function getName() {
        $this->loadProfile();
        return $this->profile->name;
    }

    public function getScreenName() {
        $this->loadProfile();
        return $this->profile->screen_name;
    }

    public function getSocialId() {
        $this->loadProfile();
        return $this->profile->id;
    }

    public function myImageURL() {
        $this->loadProfile();
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

    public static function renderShare($url) {
        JS::add('https://platform.twitter.com/widgets.js');
        $via = Configuration::get('social.twitter.url');
        if ($via) {
            $via = 'via="' . $via . '"';
        }
        return '<a class="twitter-share-button" ' . $via . ' rel="canonical" href="https://twitter.com/intent/tweet" url="' . $url . '">Tweet</a>';
    }

    public static function renderFollow() {
        if ($url = Configuration::get('social.twitter.url')) {
            JS::add('https://platform.twitter.com/widgets.js');
            return '<a class="twitter-follow-button"  href="https://twitter.com/' . $url . '">Follow @' . $url . '</a>';
        }
    }

    /**
     * Render the follow and tweet links.
     */
    public static function renderLinks() {
        $settings = Configuration::get('social.twitter');
        if (!empty($settings['follow']) || !empty($settings['share'])) {
            // Add the initialization script.

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

    /**
     * Create a social signin button for twitter.
     *
     * @param boolean $authorize
     *   This might not be used, if twitter always authorizes.
     *
     * @return string
     *   The HTML building the sign in button.
     */
    public static function loginButton($authorize = false) {
        JS::set('token', Session::getInstance()->getToken());
        JS::startup('lightning.social.initLogin()');

        self::loadAutoLoader();
        $appId = Configuration::get('social.twitter.key');
        $secret = Configuration::get('social.twitter.secret');
        $connection = new TwitterOAuth($appId, $secret);
        $oauth_callback = Configuration::get('social.twitter.oauth_callback', null)
            ?: Configuration::get('web_root') . '/user/twitterauth';
        $request_token = $connection->oauth('oauth/request_token', array('oauth_callback' => $oauth_callback));

        // Save the token to the session.
        $session = Session::getInstance();
        if (empty($session->content->twitter)) {
            $session->content->twitter = new stdClass();
        }
        $session->content->twitter->oauth_token = $request_token['oauth_token'];
        $session->content->twitter->oauth_token_secret = $request_token['oauth_token_secret'];
        $session->save();

        $url = $connection->url('oauth/authorize', array('oauth_token' => $request_token['oauth_token']));
        JS::set('social.twitter.signin_url', $url);
        return '<span class="social-signin twitter"><i class="fa fa-twitter"></i><span> Sign in with Twitter</span></span>';
    }

    public function share($text, $settings = []) {
        // Add the URL to the tweet.
        if (!empty($settings['url'])) {
            $text .= ' ' . $settings['url'];
        }
        $parameters = ['status' => $text];
        if (!empty($settings['images'])) {
            // Upload images.
            $image_ids = [];
            $images = is_array($settings['images']) ? $settings['images'] : [$settings['images']];
            foreach ($images as $image) {
                $result = $this->connection->upload('media/upload', ['media' => $image['location']]);
                $image_ids[] = $result->media_id_string;
            }
            // Add the images to the tweet.
            $parameters['media_ids'] = implode(',', $image_ids);
        }
        // Send the tweet.
        $this->connection->post('statuses/update', $parameters);
    }
}
