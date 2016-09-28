<?php

namespace Lightning\Tools\SocialDrivers;

use CURLFile;
use Facebook\Entities\SignedRequest;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Lightning\Tools\Configuration;
use Lightning\Tools\Scrub;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\Facebook\SDK;
use Lightning\View\JS;
use stdClass;

class Facebook extends SocialMediaApi {

    const EMAIL_SUFFIX = 'facebook.com';

    protected $service;

    protected $network = 'facebook';

    public static function createInstance($token = null, $authorize = false) {
        require_once HOME_PATH . '/Lightning/Vendor/facebooksdk/autoload.php';
        $fb = new static();
        if (!empty($token)) {
            $fb->setToken($token, $authorize);
        } else {
            $session = Session::getInstance(true, false);
            if (!empty($session->content->facebook->token)) {
                $fb->setToken((array) $session->content->facebook->token, $authorize);
            }
        }

        return $fb;
    }

    public function getService() {
        return $this->service;
    }

    public function loadProfile() {
        if (empty($this->profile)) {
            $request = new FacebookRequest($this->service, 'GET', '/me');
            $this->profile = $request->execute()->getGraphObject()->asArray();
        }
    }

    public function getLightningUserData() {
        $this->loadProfile();
        return [
            'first' => !empty($this->profile['first_name']) ? $this->profile['first_name'] : '',
            'last' => !empty($this->profile['last_name']) ? $this->profile['last_name'] : '',
            'alt_email' => !empty($this->profile['email']) ? $this->profile['email'] : '',
        ];
    }

    public function getName() {
        if (!empty($this->profile['name'])) {
            return $this->profile['name'];
        }
        if (!empty($this->profile['first_name'])) {
            $name[] = $this->profile['first_name'];
        }
        if (!empty($this->profile['last_name'])) {
            $name[] = $this->profile['last_name'];
        }
        return implode(' ', $name);
    }

    public function getScreenName() {
        return '';
    }

    public function getSocialId() {
        $this->loadProfile();
        return $this->profile['id'];
    }

    /**
     * Set the token data.
     *
     * @param array $token
     *   The token data.
     * @param boolean $authorize
     *   Whether authorization is required to transmit data to the account.
     *
     * @throws \Facebook\FacebookRequestException
     */
    public function setToken($token, $authorize = false) {
        $this->token = $token;
        $this->authorize = $authorize;

        $appId = Configuration::get('social.facebook.appid');
        $secret = Configuration::get('social.facebook.secret');
        FacebookSession::setDefaultApplication($appId, $secret);

        if ($this->authorize) {
            $this->service = new FacebookSession($this->token['token']);
            if ($this->token['type'] == 'short' && $this->authorize) {
                // Convert the short token to a long token.
                $request = new FacebookRequest($this->service, 'GET', '/oauth/access_token', [
                    'grant_type' => 'fb_exchange_token',
                    'client_id' => $appId,
                    'client_secret' => $secret,
                    'fb_exchange_token' => $this->token['token']
                ]);
                $bearer_token = $request->execute()->getGraphObject()->asArray();
                $this->token['token'] = $bearer_token['access_token'];
                $this->token['type'] = 'bearer';
                // Save the updated token.
                $this->storeSessionData();
            }
        } else {
            $this->service = FacebookSession::newSessionFromSignedRequest(new SignedRequest($token['token']));
            $this->profile = $this->loadProfile();
            $this->social_id = $this->service->getUserID();
        }
    }

    public function getToken() {
        return json_encode($this->token);
    }

    public function storeSessionData() {
        $session = Session::getInstance();
        if (empty($session->content->facebook)) {
            $session->content->facebook = new stdClass();
        }
        $session->content->facebook->token = (object) $this->token;
        $session->save();
    }

    public function myImageURL() {
        $request = new FacebookRequest($this->service, 'GET', '/me/picture?redirect=0&width=800');
        $response = $request->execute()->getGraphObject()->asArray();
        return $response['url'];
    }

    public function myImageData() {
        return file_get_contents($this->myImageURL());
    }

    public function postImage($image_path) {
        $request = new FacebookRequest($this->service, 'POST', '/' . $this->getSocialId() . '/photos', [
            'source' => new CURLFile($image_path)
        ]);
        $response = $request->execute()->getGraphObject()->asArray();
        return $response;
    }

    public function getFriends() {
        $request = new FacebookRequest($this->service, 'GET', '/me/friends');
        $response = $request->execute()->getGraphObject()->asArray();
        return $response;
    }

    public function getFriendIDs() {
        $friends = $this->getFriends();
        return $friends;
    }

    /**
     * Return a list of manageable pages.
     */
    public function getPages() {
        $request = new FacebookRequest($this->service, 'GET', '/me/accounts');
        $response = $request->execute()->getGraphObject()->asArray();
        return $response['data'];
    }

    /**
     * Create a like button.
     *
     * @param array $options
     *   A list of options. Can include:
     *     - url: A url to associate the like.
     * @return string
     */
    public static function renderLike($options = []) {
        SDK::init();
        $url = !empty($options['url']) ? 'data-href="' . Scrub::toHTML($options['url']) . '"' : '';
        return '<div class="fb-like" data-layout="standard" ' . $url . ' data-action="like" data-show-faces="true" data-share="true"></div>';
    }

    /**
     * Render the share link.
     */
    public static function renderShare($url) {
        SDK::init();
        $count = Configuration::get('social.share_count');
        switch ($count) {
            case SocialMediaApi::COUNT_HORIZONTAL:
                $layout = 'button_count';
                break;
            case SocialMediaApi::COUNT_VERTICAL:
                $layout = 'box_count';
                break;
            case SocialMediaApi::COUNT_NONE:
            default:
                $layout = 'button';
                break;
        }
        return '<div class="fb-share-button" href="' . $url . '" data-layout="' . $layout . '"></div>';
    }

    public static function renderFollow() {
        if ($fb_page = Configuration::get('social.facebook.url')) {
            SDK::init();
            $count = Configuration::get('social.share_count');
            switch ($count) {
                case SocialMediaApi::COUNT_HORIZONTAL:
                    $layout = 'button_count';
                    break;
                case SocialMediaApi::COUNT_VERTICAL:
                    $layout = 'box_count';
                    break;
                case SocialMediaApi::COUNT_NONE:
                default:
                    $layout = 'button';
                    break;
            }
            return '<div class="fb-like" data-href="' . $fb_page . '" data-layout="' . $layout . '" ></div>';
        }
        return '';
    }

    public static function loginButton($authorize = false) {
        JS::add('//connect.facebook.net/en_US/sdk.js');
        JS::set('token', Session::getInstance()->getToken());
        JS::set('social.authorize', $authorize);
        SDK::init();
        JS::startup('lightning.social.initLogin()', '//connect.facebook.net/en_US/sdk.js');

        return '<span class="social-signin facebook"><i class="fa fa-facebook"></i><span> Sign in with Facebook</span></span>';
    }

    public function share($text, $settings = []) {
        $parameters = [
            'message' => $text,
            'link' => $settings['url'],
        ];
        if (!empty($settings['images'])) {
            // Add the image.
            $parameters['picture'] = $settings['images'][0]['url'];
        }
        // Send the tweet.
        $request = new FacebookRequest($this->service, 'POST', '/me/feed', $parameters);
        $response = $request->execute()->getGraphObject()->asArray();
    }
}
