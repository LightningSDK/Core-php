<?php

namespace lightningsdk\core\API;

use lightningsdk\core\Model\Message;
use lightningsdk\core\Model\User;
use lightningsdk\core\Tools\Mailer;
use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\Tools\Session\BrowserSession;
use lightningsdk\core\View\API;

/**
 * Class Optin
 * @package lightningsdk\core\API
 *
 * @deprecated use Lighting\API\Contact instead
 */
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

        // Add the user id to the browser session to remember them
        $session = BrowserSession::getInstance();
        $session->user_id = $user->id;
        $session->save();

        // Send a message
        if ($message) {
            $mailer = new Mailer();
            $this->userMessageSent = $mailer->sendOne($message, $user);
        }

        $message = array_key_exists('success', $_POST) ? Scrub::toHTML($_POST['success']) : 'Thank you for subscribing.';
        Messenger::message($message);

        return Output::SUCCESS;
    }
}
