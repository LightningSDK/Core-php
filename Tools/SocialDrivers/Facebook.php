<?php

namespace Lightning\Tools\SocialDrivers;

use Facebook\Entities\SignedRequest;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Lightning\Tools\Configuration;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\JS;

class Facebook extends SocialMediaApi {

    const EMAIL_SUFFIX = 'facebook.com';

    protected $service;

    public static function createInstance($token = null, $authorize = false) {
        include HOME_PATH . '/Lightning/Vendor/facebooksdk/autoload.php';
        $fb = new static();
        if (!empty($token)) {
            $fb->setToken($token, $authorize);
        } elseif ($token = Session::getInstance(true, false)->getSetting('facebook.token')) {
            $fb->setToken($token, $authorize);
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
            'first' => $this->profile['first_name'],
            'last' => $this->profile['last_name'],
            'alt_email' => $this->profile['email'],
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
        $session->setSetting('facebook.token', $this->token);
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
     * Render the like and share links.
     */
    public static function renderLinks() {
        $settings = Configuration::get('social.facebook');
        if (!empty($settings['share']) || !empty($settings['like'])) {
            JS::startup("!function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = '//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.3';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk');");
            Template::getInstance()->addFooter('<div id="fb-root"></div>');
            $output = '';
            if (!empty($settings['share'])) {
                $output .= '<div class="fb-share-button" data-layout="button"></div>';
            }
            if (!empty($settings['like']) && !empty($settings['page'])) {
                $output .= '<div class="fb-like" data-href="https://facebook.com/' . $settings['page'] . '" data-layout="button" data-action="like" data-show-faces="false" data-share="false"></div>';
            }
            return $output;
        }
    }

    public static function loginButton($authorize = false) {
        JS::set('token', Session::getInstance()->getToken());
        JS::set('social.authorize', $authorize);
        JS::set('social.facebook.appid', Configuration::get('social.facebook.appid'));
        JS::startup('lightning.social.initLogin()');

        return '<span class="social-signin facebook"><i class="fa fa-facebook"></i><span> Sign in with Facebook</span></span>';
    }
}
