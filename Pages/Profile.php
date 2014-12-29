<?php

namespace Lightning\Pages;

use Lightning\Model\Subscription;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\Page;

class Profile extends Page {

    protected $page = 'profile';

    protected $nav = 'profile';

    protected function hasAccess() {
        ClientUser::requireLogin();
        return true;
    }

    public function get() {
        // Load mailing list preferences.
        $all_lists = Subscription::getLists();
        $mailing_lists = Subscription::getUserLists(ClientUser::getInstance()->id);
        Template::getInstance()->set('all_lists', $all_lists);
        Template::getInstance()->set('mailing_lists', $mailing_lists);
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

        // Update mailing list preferences.
        $new_lists = Request::get('subscribed', 'array', 'int');
        $new_lists = array_combine($new_lists, $new_lists);
        $all_lists = Subscription::getLists();
        $user_id = ClientUser::getInstance()->id;
        $user_lists = Subscription::getUserLists($user_id);
        $remove_lists = array();
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
            $db->delete('message_list_user', array('message_list_id' => array('IN', $remove_lists), 'user_id' => $user_id));
        }
        if (!empty($add_lists)) {
            $db->insertMultiple('message_list_user', array('message_list_id' => $add_lists, 'user_id' => $user_id), true);
        }

        if (count(Messenger::getErrors()) == 0) {
            Navigation::redirect(null, array('msg' => 'saved'));
        }
    }
}
