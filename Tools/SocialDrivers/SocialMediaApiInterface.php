<?php

namespace Lightning\Tools\SocialDrivers;

interface SocialMediaApiInterface {
    const EMAIL_SUFFIX = '';

    public function setupUser();

    public function setToken($token);
    public function getToken();

    public function storeSessionData();

    public function getLightningUserData();
    public function loadProfile();
    public function getFriends();
    public function getFriendIDs();
    public static function loginButton();
    public function getSocialId();
    public function activateUser();
    public function afterLogin();
    public function getName();
    public function getScreenName();
    public function share($text, $settings = []);
}
