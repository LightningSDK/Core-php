<?php

namespace Lightning\Tools\SocialDrivers;

use Lightning\Pages\User;
use Lightning\Tools\Messenger;
use Lightning\View\Page;

class TwitterAuthPage extends Page {
    public function hasAccess() {
        return true;
    }

    public function get() {
        if ($token = Twitter::getAccessToken()) {
            $twitter = Twitter::getInstance(true, $token);

            $user_page = new User();
            $user_page->finishSocialLogin($twitter);
        }
        Messenger::error('Login Failed');
        $this->redirect('/user');
    }
}
