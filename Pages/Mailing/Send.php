<?php

namespace Lightning\Pages\Mailing;

use Lightning\Model\Message;
use Lightning\Tools\Database;
use Lightning\Tools\Mailer;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\Page;

class Send extends Page {
    public function get() {
        $message_id = Request::get('id', 'int');
        if (!$message_id || !$message = Database::getInstance()->selectRow('message', array('message_id' => $message_id))) {
            Messenger::error('Message not found.');
            return;
        }

        $template = Template::getInstance();
        $template->set('content', 'mailing_send');
        $template->set('message', $message);
    }

    public function postSendAll() {
        Output::disableBuffering();
        $mailer = new Mailer(true);
        $mailer->sendBulk(Request::get('id', 'int'), false);
        exit;
    }

    public function postSendCount() {
        $message = new Message(Request::get('id', 'int'));
        echo 'Sending now will go to ' . $message->getUsersCount() . ' users.';
        exit;
    }

    public function postSendTest() {
        Output::disableBuffering();
        $mailer = new Mailer(true);
        $mailer->sendBulk(Request::get('id', 'int'), true);
        exit;
    }
}
