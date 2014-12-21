<?php

namespace Lightning\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\Model\User;

class OptIn extends Page {
    public function get() {
        Template::getInstance()->set('content', 'landing');
    }

    public function post() {
        // Add the user to the system.
        $name = array(
            'first' => Request::post('first'),
            'last' => Request::post('last'),
        );
        $email = Request::post('email', 'email');
        $user = User::addUser($email, $name);

        // Add the user to the mailing list.
        $default_list = Configuration::get('mailer.default_list');
        $mailing_list = Request::post('list_id', 'int', null, $default_list);
        if (!empty($mailing_list)) {
            $user->subscribe($mailing_list);
        }

        Navigation::redirect(Request::post('redirect') ?: '/message?msg=optin');
    }
}
