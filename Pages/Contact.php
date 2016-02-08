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
use Lightning\View\Page as PageView;
use Lightning\Model\User as UserModel;

/**
 * A contact page handler.
 *
 * @package Lightning\Pages
 */
class Contact extends PageView {

    protected $page = 'contact';
    protected $nav = 'contact';

    protected $sender_email;

    protected function hasAccess() {
        return true;
    }

    /**
     * Send a posted contact request to the site admin.
     */
    public function postSendMessage() {
        // Make sure the sender's email address is valid.
        if (!$this->getSender()) {
            Messenger::error('Please enter a valid email address.');
            return $this->get();
        }

        if (!ReCaptcha::verify()) {
            Messenger::error('You did not correctly enter the captcha code.');
            return $this->get();
        }

        $sent = $this->sendMessage();

        if (!$sent) {
            Messenger::error('Your message could not be sent. Please try again later');
            return $this->get();
        } else {
            // Send an email to to have them test for spam.
            if ($auto_responder = Configuration::get('contact.auto_responder')) {
                $auto_responder_mailer = new Mailer();
                $result = $auto_responder_mailer->sendOne($auto_responder, UserModel::loadByEmail($this->getSender()) ?: new UserModel(array('email' => $this->getSender())));
                if ($result && Configuration::get('contact.spam_test')) {
                    // Set the notice.
                    Navigation::redirect('/message', array('msg' => 'spam_test'));
                }
            }
            Navigation::redirect('/message', array('msg' => 'contact_sent'));
        }
    }

    protected function getMessageBody() {
        $fields = array_combine(array_keys($_POST), array_keys($_POST));
        $values = [
            'Name' => Request::post('name'),
            'Email' => $this->getSender(),
            'IP' => Request::server(Request::IP),
        ];
        $message = Request::post('message');

        unset($fields['token']);
        unset($fields['name']);
        unset($fields['email']);
        unset($fields['message']);

        foreach ($fields as $field) {
            $values[ucfirst(preg_replace('/_/', ' ', $field))] = Request::post($field);
        }

        $output = '';
        foreach ($values as $key => $value) {
            $output .= $key . ': ' . $value . "<br>\n";
        }
        $output .= "Message: <br>\n" . $message;
        return $output;
    }

    protected function getSender() {
        if (empty($this->sender_email)) {
            $this->sender_email = Request::post('email', 'email');
        }
        return $this->sender_email;
    }

    public function sendMessage() {
        $subject = Configuration::get('contact.subject');
        $to_addresses = Configuration::get('contact.to');

        $mailer = new Mailer();
        foreach ($to_addresses as $to) {
            $mailer->to($to);
        }
        return $mailer
            ->from($this->getSender())
            ->subject($subject)
            ->message($this->getMessageBody())
            ->send();
    }
}
