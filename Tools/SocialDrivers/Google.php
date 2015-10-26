<?php

namespace Lightning\Tools\SocialDrivers;

use Google_Client;
use Google_Service_Plus;
use Google_Service_Plus_Person;
use Lightning\Tools\Configuration;
use Lightning\View\JS;

class Google extends SocialMediaApi {

    /**
     * @var Google_Service_Plus
     */
    public $service;

    /**
     * @var Google_Service_Plus_Person
     */
    protected $me;

    public static function createInstance($token = null) {
        include HOME_PATH . '/Source/Vendor/googleapiclient/src/Google/autoload.php';
        $appId = Configuration::get('social.google.clientid');
        $secret = Configuration::get('social.google.secret');

        $client = new Google_Client();
        $client->setClientId($appId);
        $client->setClientSecret($secret);

        if (!empty($token)) {
            $client->setAccessToken($token);
        }
        $google = new static();
        $google->service = new Google_Service_Plus($client);
        return $google;
    }

    protected function loadProfile() {
        if (empty($this->me)) {
            $this->me = $this->service->people->get('me');
        }
    }

    public function authenticate() {
        $this->loadProfile();
        return !empty($this->me);
    }

    public function getLightningUserData() {
        $this->loadProfile();
        $user_settings = [
            'first' => $this->me->getName()->getGivenName(),
            'last' => $this->me->getName()->getFamilyName(),
        ];
        $emails = $this->me->getEmails();
        if (!empty($emails)) {
            $user_settings['alt_email'] = $emails[0];
        }
        return $user_settings;
    }

    public function getLightningEmail() {
        return $this->me->getId() . '@@google.com';
    }

    public function myImageURL() {
        return $this->me->getImage()->getUrl();
    }

    public function myImageData() {
        return file_get_contents($this->myImageURL());
    }

    public function myFriends() {
        return $this->service->people->listPeople('me', 'visible');
    }

    public static function renderLinks() {
        $settings = Configuration::get('social.google');
        if (!empty($settings['like'])) {
            JS::add('https://apis.google.com/js/platform.js', true);
            $output = '<g:plusone size="medium" annotation="none"></g:plusone>';

            return $output;
        }
    }
}
