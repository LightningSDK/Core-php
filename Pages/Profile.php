<?php

namespace Lightning\Pages;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\View\Page;

class Profile extends Page {

    protected $page = 'profile';

    protected $nav = 'profile';

    public function get() {
        ClientUser::requireLogin();
    }

    public function postSave() {
        $user = ClientUser::getInstance();

        // Update the user name.
        $user->update(array('first' => Request::get('first'), 'last' => Request::get('last')));

        // Update the password.
        $password = Request::post('password');
        $new_password = Request::post('new_password');
        $new_password_confirm = Request::post('new_password_confirm');
        if (!empty($password) && $user->checkPass($password)) {
            if (false) {
                Messenger::error('Your password did not meet the required criteria.');
            } elseif ($new_password != $new_password_confirm) {
                Messenger::error('You did not enter the same password twice.');
            } else {
                $user->setPass($new_password);
            }
        } elseif (!empty($new_password) || !empty($new_password)) {
            Messenger::error('You did not enter your correct current password.');
        }

        if (count(Messenger::getErrors()) == 0) {
            Navigation::redirect(null, array('msg' => 'saved'));
        }
    }
}
