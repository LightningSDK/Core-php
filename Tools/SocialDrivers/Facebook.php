<?php

namespace Lightning\Tools\SocialDrivers;

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

    public static function createInstance($token = null, $authorize = false) {
        include HOME_PATH . '/Lightning/Vendor/facebooksdk/autoload.php';
        $fb = new static();
        if (!empty($token)) {
            $fb->setToken($token, $authorize);
        } else {
            $session = Session::getInstance(true, false);
            if (!empty($session->content->facebook->token)) {
                $fb->setToken($session->content->facebook->token, $authorize);
            }
        }
        return $fb;
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

    public function getSocialId() {
        $this->loadProfile();
        return $this->profile['id'];
    }

    public function setToken($token, $authorize = false) {
        $this->token = $token;
        $this->authorize = $authorize;

        $appId = Configuration::get('social.facebook.appid');
        $secret = Configuration::get('social.facebook.secret');
        FacebookSession::setDefaultApplication($appId, $secret);

        if ($authorize) {
            $this->service = new FacebookSession($token);
        } else {
            $this->service = FacebookSession::newSessionFromSignedRequest(new SignedRequest($token));
            $this->profile = $this->loadProfile();
            $this->social_id = $this->service->getUserID();
        }
    }

    public function storeSessionData() {
        $session = Session::getInstance();
        if (empty($session->content->facebook)) {
            $session->content->facebook = new stdClass();
        }
        $session->content->facebook->token = $this->token;
        $session->save();
    }

    public function myImageURL() {
        $request = new FacebookRequest($this->service, 'GET', '/me/picture?redirect=0');
        $response = $request->execute()->getGraphObject()->asArray();
        return $response['url'];
    }

    public function myImageData() {
        return file_get_contents($this->myImageURL());
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
        JS::set('token', Session::getInstance()->getToken());
        JS::set('social.authorize', $authorize);
        JS::set('social.facebook.appid', Configuration::get('social.facebook.appid'));
        JS::startup('lightning.social.initLogin()');

        return '<span class="social-signin facebook"><i class="fa fa-facebook"></i><span> Sign in with Facebook</span></span>';
    }
}
