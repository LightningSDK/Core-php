<?php

namespace Lightning\API;

use Lightning\Model\Message;
use Lightning\Model\User;
use Lightning\Tools\Mailer;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\API;

class Optin extends API {
    public function post() {
        $name = Request::get('name');
        $email = Request::get('email', Request::TYPE_EMAIL);
        $list = Request::get('list', Request::TYPE_INT);
        $message = Request::get('message', Request::TYPE_INT);
        if (empty($list)) {
            $list = Message::getDefaultListID();
        } else {
            Message::validateListID($list);
        }

        // Subscribe the user
        $user = User::addUser($email, ['full_name' => $name]);
        $user->subscribe($list);

        // Send a message
        if ($message) {
            $mailer = new Mailer();
            $this->userMessageSent = $mailer->sendOne($message, $user);
        }

        Messenger::message('Thank you for subscribing.');

        return Output::SUCCESS;
    }
}
