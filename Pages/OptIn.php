<?php

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Language;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\Model\User;

class OptIn extends Page {

    protected $page = 'optin';

    protected function hasAccess() {
        return true;
    }

    public function post() {

        if ($name = Request::post('name', '', '', '')) {
            $name_parts = explode(' ', $name, 2);
            $name = array('first' => $name_parts[0]);
            if (!empty($name_parts[1])) {
                $name['last'] = $name_parts[1];
            }
        } else {
            $name = array(
                'first' => Request::post('first', '', '', ''),
                'last' => Request::post('last', '', '', ''),
            );
        }

        // Add the user to the database.
        $email = Request::post('email', 'email');
        $user = User::addUser($email, $name);

        // Add the user to the mailing list.
        $default_list = Configuration::get('mailer.default_list');
        $mailing_list = Request::post('list_id', 'int', null, $default_list);
        if (!empty($mailing_list)) {
            $user->subscribe($mailing_list);
        }

        // Send out an email.
        if (Configuration::get('contact.optin')) {
            $contact = new Contact();
            $contact->sendMessage();
        }

        Messenger::message(Language::translate('optin.success'));
        Navigation::redirect(Request::post('redirect') ?: '/message');
    }
}
