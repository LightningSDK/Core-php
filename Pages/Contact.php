<?php
/**
 * @file
 * Contains Lightning\Pages\Page
 */

namespace Lightning\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Form;
use Lightning\Tools\Mailer;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\ReCaptcha;
use Lightning\Tools\Request;
use Lightning\View\Page;
use Lightning\Model\User as UserModel;

/**
 * A contact page handler.
 *
 * @package Lightning\Pages
 */
class Contact extends Page {

    protected $page = 'contact';
    protected $nav = 'contact';

    protected function hasAccess() {
        return true;
    }

    /**
     * Build the contact form.
     */
    public function get() {
        Form::requiresToken();
    }

    /**
     * Send a posted contact request to the site admin.
     */
    public function postSendMessage() {
        // Make sure the sender's email address is valid.
        if (!$sender_email = Request::post('email', 'email')) {
            Messenger::error('Please enter a valid email address.');
            return $this->get();
        }

        if (!ReCaptcha::verify()) {
            Messenger::error('You did not correctly enter the captcha code.');
            return $this->get();
        }

        $subject = Configuration::get('contact.subject');
        $body = "
Name: {$_POST['name']}
Email: {$sender_email}
Message:
{$_POST['message']}";
        $to_addresses = Configuration::get('contact.to');

        $mailer = new Mailer();
        foreach ($to_addresses as $to) {
            $mailer->to($to);
        }
        $sent = $mailer
            ->from($sender_email)
            ->subject($subject)
            ->message($body)
            ->send();

        if (!$sent) {
            Messenger::error('Your message could not be sent. Please try again later');
            return $this->get();
        } else {
            // Send an email to to have them test for spam.
            if ($auto_responder = Configuration::get('contact.auto_responder')) {
                $auto_responder_mailer = new Mailer();
                $result = $auto_responder_mailer->sendOne($auto_responder, UserModel::loadByEmail($sender_email) ?: new UserModel(array('email' => $sender_email)));
                if ($result && Configuration::get('contact.spam_test')) {
                    // Set the notice.
                    Navigation::redirect('/message', array('msg' => 'spam_test'));
                }
            }
            Navigation::redirect('/message', array('msg' => 'contact_sent'));
        }
    }
}
