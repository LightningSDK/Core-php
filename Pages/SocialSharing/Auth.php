<?php

namespace Lightning\Pages\SocialSharing;

use Lightning\Model\Permissions;
use Lightning\Model\SocialAuth;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\SocialDrivers\Facebook;
use Lightning\Tools\SocialDrivers\Google;
use Lightning\Tools\SocialDrivers\SocialMediaApi;
use Lightning\Tools\SocialDrivers\Twitter;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Lightning\View\Page;

class Auth extends Page {

    protected $page = ['admin/social/auth', 'Lightning'];

    public function __construct() {
        parent::__construct();

        // Override the facebook scope with required additions.
        $current_scope = Configuration::get('social.facebook.scope');
        $new_scope = implode(',', array_unique(array_merge(explode(',', $current_scope), explode(',', 'pages_show_list,manage_pages,publish_pages,public_profile,publish_actions'))));
        Configuration::set('social.facebook.scope', $new_scope);

        // Override the google scope with required additions.
        $current_scope = Configuration::get('social.google.scope');
        $new_scope = implode(' ', array_unique(array_merge(explode(' ', $current_scope), [
            'https://www.googleapis.com/auth/plus.stream.write',
            'https://www.googleapis.com/auth/plus.me',
        ])));
        Configuration::set('social.google.scope', $new_scope);
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
            'network' => $api->getNetwork(),
            'name' => $api->getName(),
            'screen_name' => $api->getScreenName(),
        ]);
        $social_auth->save();
    }
}
