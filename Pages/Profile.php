<?php

namespace lightningsdk\core\Pages;

use Exception;
use lightningsdk\core\Model\Subscription;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\Tools\Navigation;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\Page;

class Profile extends Page {

    protected $page = ['profile', 'lightningsdk/core'];

    protected $nav = 'profile';

    protected function hasAccess() {
        ClientUser::requireLogin();
        return true;
    }

    public function get() {
        // Load mailing list preferences.
        $all_lists = Subscription::getLists();
        $user = ClientUser::getInstance();
        $mailing_lists = Subscription::getUserLists($user->id);
        $template = Template::getInstance();
        $template->set('user', $user);
        $template->set('all_lists', $all_lists);
        $template->set('mailing_lists', $mailing_lists);
    }

    public function postSave() {
        $user = ClientUser::getInstance();

        // Update the user name.
        $user->first = Request::get('first');
        $user->last = Request::get('last');
        $user->timezone = Request::get('timezone');

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
        $user->save();

        try {
            $user->email = Request::get('email', Request::TYPE_EMAIL);
            $user->save();
        } catch (Exception $e) {
            // The email could not be set.
            Messenger::error('An account with that email already exists.');
        }

        // Update mailing list preferences.
        $new_lists = Request::get('subscribed', 'array', 'int', []);
        $new_lists = array_combine($new_lists, $new_lists);
        $all_lists = Subscription::getLists();
        $user_lists = Subscription::getUserLists($user->id);
        $remove_lists = [];
        foreach ($user_lists as $list) {
            if (empty($new_lists[$list['message_list_id']]) && !empty($list['visible'])) {
                $remove_lists[$list['message_list_id']] = $list['message_list_id'];
            }
        }
        $add_lists = $new_lists;
        unset($add_lists[0]);
        if (!isset($new_lists[0])) {
            foreach ($all_lists as $list) {
                if (empty($list['visible'])) {
                    $remove_lists[$list['message_list_id']] = $list['message_list_id'];
                }
            }
        }

        $db = Database::getInstance();
        if (!empty($remove_lists)) {
            $db->delete('message_list_user', ['message_list_id' => ['IN', $remove_lists], 'user_id' => $user->id]);
        }
        if (!empty($add_lists)) {
            $db->insertMultiple('message_list_user', ['message_list_id' => $add_lists, 'user_id' => $user->id], true);
        }

        Navigation::redirect();
    }
}
