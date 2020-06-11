<?php

namespace lightningsdk\core\Tools\SocialDrivers;

use lightningsdk\core\Pages\User;
use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\View\Page;

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
