<?php

namespace Lightning\Tools\SocialDrivers;

use Google_Client;
use Google_Service_Plus;
use Google_Service_Plus_Activity;
use Google_Service_Plus_ActivityObject;
use Google_Service_Plus_ActivityObjectAttachments;
use Google_Service_PlusDomains;
use Lightning\Tools\Configuration;
use Lightning\Tools\Form;
use Lightning\Tools\Session;
use Lightning\Tools\Session\BrowserSession;
use Lightning\View\JS;
use stdClass;

class Google extends SocialMediaApi {

    const EMAIL_SUFFIX = 'google.com';

    protected $network = 'google';

    /**
     * @var Google_Service_Plus
     */
    public $service;

    protected static $isApp = false;

    public static function setApp($app) {
        self::$isApp = $app;
    }

    public static function createInstance($token = null, $authorize = false) {
        include HOME_PATH . '/Lightning/Vendor/googleapiclient/src/Google/autoload.php';

        $google = new static();
        if (!empty($token)) {
            $google->setToken($token, $authorize);
        } else {
            $session = Session::getInstance(true, false);
            if (!empty($session->content->google->token)) {
                $google->setToken($session->content->google->token, $authorize);
            }
        }

        return $google;
    }

    public function setToken($token, $authorize = false) {
        $this->token = $token;
        $this->authorize = $authorize;

        if (empty(self::$isApp)) {
            $appId = Configuration::get('social.google.client_id');
            $secret = Configuration::get('social.google.secret');
        } else {
            $appId = Configuration::get('social.google-app.client_id');
            $secret = Configuration::get('social.google-app.secret');
        }

        $this->client = new Google_Client();
        $this->client->setClientId($appId);
        $this->client->setClientSecret($secret);

        if ($authorize) {
            $this->client->setAccessToken($token);
            $this->service = new Google_Service_Plus($this->client);
        } else {
            $user_data = $this->client->verifyIdToken($token);
            $this->id_profile = $user_data->getAttributes()['payload'];
            $this->social_id = $user_data->getUserId();
        }
    }

    public function getToken() {
        return $this->token;
    }

    public function storeSessionData() {
        $session = Session::getInstance();
        if (empty($session->content->google)) {
            $session->content->google = new stdClass();
        }
        $session->content->google->token = $this->token;
        $session->save();
    }

    public function loadProfile() {
        if ($this->authorize && empty($this->profile)) {
            $this->profile = $this->service->people->get('me');
        }
    }

    public function authenticate() {
        if ($this->authorize) {
            $this->loadProfile();
            return !empty($this->me);
        } else {
            return !empty($this->id_profile);
        }
    }

    public function getLightningUserData() {
        if ($this->authorize) {
            $this->loadProfile();
            $user_settings = [
                'first' => $this->profile->getName()->getGivenName(),
                'last' => $this->profile->getName()->getFamilyName(),
            ];
            $emails = $this->profile->getEmails();
            if (!empty($emails)) {
                $user_settings['alt_email'] = $emails[0]->getValue();
            }
        } else {
            $user_settings = [
                'first' => $this->id_profile['given_name'],
                'last' => $this->id_profile['family_name'],
                'alt_email' => $this->id_profile['email'],
            ];
        }
        return $user_settings;
    }

    public function getSocialId() {
        if ($this->authorize) {
            $this->loadProfile();
            return $this->profile->getId();
        } else {
            return $this->social_id;
        }
    }

    public function myImageURL() {
        if ($this->authorize) {
            return $this->profile->getImage()->getUrl();
        } else {
            return $this->id_profile['picture'];
        }
    }

    public function myImageData() {
        return file_get_contents($this->myImageURL());
    }

    public function getFriends() {
        return $this->service->people->listPeople('me', 'visible');
    }

    public function getName() {
        $this->loadProfile();
        return $this->profile->getName()->getGivenName()
          . ' ' . $this->profile->getName()->getFamilyName();
    }

    public function getScreenName() {
        return '';
    }

    public function getFriendIDs() {
        $friends = $this->getFriends();
        $ids = [];
        foreach ($friends as $f) {
            $ids[] = $f->id;
        }

        return $ids;
    }

    public function getPages() {

    }

    public static function renderLike() {
        JS::add('https://apis.google.com/js/platform.js', true);
        return '<div class="g-plusone" ' . self::getLayout() . '></div>';
    }

    public static function renderShare($url) {
        JS::add('https://apis.google.com/js/platform.js', true);
        return '<div class="g-plus" ' . self::getLayout() . ' data-action="share" data-href="' . $url . '"></div>';
    }

    public static function renderFollow() {
        if ($url = Configuration::get('social.google.url')) {
            JS::add('https://apis.google.com/js/platform.js', true);
            return '<div class="g-follow" ' . self::getLayout() . ' data-href="' . $url . '" data-rel="publisher"></div>';
        }
    }

    protected static function getLayout() {
        $count = Configuration::get('social.share_count');
        switch ($count) {
            case SocialMediaApi::COUNT_HORIZONTAL:
                $layout = 'data-annotation="bubble"';
                break;
            case SocialMediaApi::COUNT_VERTICAL:
                $layout = 'data-annotation="vertical-bubble"';
                break;
            case SocialMediaApi::COUNT_NONE:
            default:
                $layout = 'data-annotation="none" data-height="20"';
                break;
        }
        return $layout;
    }

    public static function loginButton($authorize = false) {
        JS::set('token', Form::getToken());
        JS::set('social.authorize', $authorize);
        JS::set('social.google.client_id', Configuration::get('social.google.client_id'));
        JS::set('social.google.scope', Configuration::get('social.google.scope'));
        JS::startup('lightning.social.initLogin()');

        return '<span class="social-signin google"><i class="fa fa-google"></i><span> Sign in with Google</span></span>';
    }

    public function share($text, $settings = []) {
        $this->serviceDomains = new Google_Service_PlusDomains($this->client);
        $object = new Google_Service_Plus_ActivityObject();
        $object->originalContent = $text;
        $attachment = new Google_Service_Plus_ActivityObjectAttachments();
        $attachment->setUrl($settings['url']);
        $object->setAttachments([$attachment]);
        $activity = new Google_Service_Plus_Activity();
        $activity->setObject($object);
        $this->serviceDomains->activities->insert('me', $activity);
    }
}
