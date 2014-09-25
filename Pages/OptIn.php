<?php

namespace Lightning\Pages;

use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\Model\User;

class OptIn extends Page {
    public function get() {
        Template::getInstance()->set('content', 'landing');
    }

    public function post() {
        $name = array(
            'first' => Request::post('first'),
            'last' => Request::post('last'),
        );
        $email = Request::post('email', 'email');
        $mailing_list = Request::post('list_id', 'int', null, 0);

        $user = User::addUser($email, $name);
        $user->subscribe($mailing_list);

        Navigation::redirect(Request::post('redirect') ?: '/message?msg=optin');
    }
}
