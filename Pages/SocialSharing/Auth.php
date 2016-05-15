<?php

namespace Lightning\Pages\SocialSharing;

use Lightning\Model\Permissions;
use Lightning\Model\SocialAuth;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\SocialDrivers\Facebook;
use Lightning\Tools\SocialDrivers\Google;
use Lightning\Tools\SocialDrivers\SocialMediaApi;
use Lightning\Tools\SocialDrivers\Twitter;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Lightning\View\Page;

class Auth extends Page {

    protected $page = 'admin/social/auth';

    public function __construct() {
        // Custom construct and app ID override
        parent::__construct();
        $overlay = Database::getInstance()->selectRow('overlay', ['site_id' => 16]);
        Configuration::set('social.facebook', [
            'appid' => $overlay['app_id'],
            'secret' => $overlay['secret'],
            // This line can stay if this module is converted publicly.
            'scope' => 'pages_show_list,manage_pages,publish_pages,public_profile,publish_actions'
        ]);
    }

    public function hasAccess() {
        return ClientUser::requirePermission(Permissions::ALL);
    }

    public function get() {
        $authorizations = SocialAuth::getAuthorizations();

        Template::getInstance()->set('authorizations', $authorizations);

        Configuration::set('social.twitter.oauth_callback', Configuration::get('web_root') . '/admin/social/auth?action=twitter_auth');
        JS::set('social.signin_url', '/admin/social/auth');
    }

    public function getTwitterAuth() {
        if ($token = Twitter::getAccessToken()) {
            $twitter = Twitter::getInstance(true, $token);
            $this->saveAuth($twitter);
        }
        $this->redirect();
    }

    public function postFacebookLogin() {
        if ($token = Facebook::getRequestToken()) {
            $facebook = Facebook::getInstance(true, $token, $token['token']);
            $this->saveAuth($facebook);
        }
        $this->redirect();
    }

    public function postGoogleLogin() {
        if ($token = Google::getRequestToken()) {
            $google = Google::getInstance(true, $token['token'], $token['auth']);
            $this->saveAuth($google);
        }
        $this->redirect();
    }

    /**
     * @param SocialMediaApi $api
     */
    protected function saveAuth($api) {
        $social_auth = new SocialAuth([
            'token' => $api->getToken(),
            'social_id' => $api->getSocialId(),
            'user_id' => ClientUser::getInstance()->id,
            'site_id' => Site::getInstance()->id,
            'network' => $api->getNetwork(),
            'name' => $api->getName(),
            'screen_name' => $api->getScreenName(),
        ]);
        $social_auth->save();
    }
}
