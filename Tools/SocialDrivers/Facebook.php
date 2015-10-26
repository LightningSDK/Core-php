<?php

namespace Lightning\Tools\SocialDrivers;

use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Lightning\Tools\Configuration;
use Lightning\Tools\Template;
use Lightning\View\JS;

class Facebook extends SocialMediaApi {

    public static function createInstance($token = null) {
        include HOME_PATH . '/Source/Vendor/facebooksdk/autoload.php';
        $appId = Configuration::get('social.facebook.appid');
        $secret = Configuration::get('social.facebook.secret');
        FacebookSession::setDefaultApplication($appId, $secret);
        $fb = new static();
        if (!empty($token)) {
            $fb->setToken($token);
        }
        return $fb;
    }

    public function setToken($token) {
        $this->session = new FacebookSession($token);
    }

    public function myProfile() {
        $request = new FacebookRequest($this->session, 'GET', '/me');
        $me = $request->execute()->getGraphObject()->asArray();
        $this->myUserId = $me['id'];
        return $me;
    }

    public function myImageURL() {
        $request = new FacebookRequest($this->session, 'GET', '/me/picture?redirect=0');
        $response = $request->execute()->getGraphObject()->asArray();
        return $response['url'];
    }

    public function myImageData() {
        return file_get_contents($this->myImageURL());
    }

    public function myFriends() {
        $request = new FacebookRequest($this->session, 'GET', '/me/friends');
        $response = $request->execute()->getGraphObject()->asArray();
        return $response;
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
}
