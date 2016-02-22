<?php

namespace Lightning\Tools\SocialDrivers;

use Abraham\TwitterOAuth\TwitterOAuth;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Session;
use Lightning\View\JS;

class LinkedIn extends SocialMediaApi {

    const EMAIL_SUFFIX = 'linkedin.com';

    public static function createInstance($token = null) {
    }

    public function setToken($token) {
    }

    public function storeSessionData() {
    }

    public static function getAccessToken() {
    }

    public function loadProfile() {
    }

    public function authenticate() {
    }

    public function getLightningUserData() {
    }

    public function getSocialId() {
    }

    public function myImageURL() {
    }

    public function myImageData() {
    }

    public function getFriends() {
    }

    public function getFriendIDs() {
    }

    public static function renderShare($url) {
        JS::add('//platform.linkedin.com/in.js');
        return '<script type="IN/Share" data-url="' . $url . '" data-counter="top"></script>';
    }

    public static function renderFollow() {
        if ($url = Configuration::get('social.linked.url')) {
            JS::add('//platform.linkedin.com/in.js');
            return '<script type="IN/FollowCompany" data-id="1337" data-counter="top"></script>';
        }
    }

    public static function loginButton() {
    }
}
