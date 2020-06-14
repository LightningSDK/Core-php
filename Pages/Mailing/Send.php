<?php
/**
 * @file
 * lightningsdk\core\Pages\Mailing\Send
 */

namespace lightningsdk\core\Pages\Mailing;

use lightningsdk\core\Model\Message;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Mailer;
use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\JS;
use lightningsdk\core\View\Page;
use lightningsdk\core\Model\Permissions;

/**
 * A page handler for the send message controls and callbacks.
 *
 * @package lightningsdk\core\Pages\Mailing
 */
class Send extends Page {

    protected $rightColumn = false;

    /**
     * Require admin privileges.
     */
    public function hasAccess() {
        return ClientUser::requirePermission(Permissions::SEND_MAIL_MESSAGES);
    }

    /**
     * The main page with options to send emails or tests.
     */
    public function get() {
        $message_id = Request::get('id', Request::TYPE_INT);
        if (!$message_id || !$message = Database::getInstance()->selectRow('message', ['message_id' => $message_id])) {
            Messenger::error('Message not found.');
            return;
        }

        $template = Template::getInstance();
        $template->set('content', ['mailing_send', 'lightningsdk/core']);
        $template->set('message', $message);
        JS::set('message_id', $message['message_id']);
        JS::addSessionToken();
    }

    /**
     * Send all the messages with streaming output for XHR monitoring.
     */
    public function postSendAll() {
        Output::disableBuffering();
        Output::setPlainText(true);
        Messenger::setVerbose(true);
        $mailer = new Mailer(true);
        $mailer->sendBulk(Request::get('id', Request::TYPE_INT), false);
        exit;
    }

    /**
     * Send the message to a random subset.
     */
    public function postSendRandom() {
        Output::disableBuffering();
        Output::setPlainText(true);
        Messenger::setVerbose(true);
        $mailer = new Mailer(true);
        $mailer->setLimit(Request::post('count', Request::TYPE_INT));
        $mailer->setRandom(true);
        $mailer->sendBulk(Request::post('id', Request::TYPE_INT), false);
        exit;
    }

    /**
     * Get a count of how many emails to be sent with output for XHR monitoring.
     */
    public function postSendCount() {
        Output::setPlainText(true);
        Messenger::setVerbose(true);
        $message = Message::loadByID(Request::post('id', Request::TYPE_INT), true, false);
        echo 'Sending now will go to ' . $message->getUsersCount() . ' users.';
        exit;
    }

    /**
     * Send a test email.
     */
    public function postSendTest() {
        Output::disableBuffering();
        Output::setPlainText(true);
        Messenger::setVerbose(true);
        $mailer = new Mailer(true);
        $mailer->sendBulk(Request::post('id', Request::TYPE_INT), true);
        exit;
    }
}
