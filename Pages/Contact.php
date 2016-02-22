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
use Lightning\Tools\Output;
use Lightning\Tools\ReCaptcha;
use Lightning\Tools\Request;
use Lightning\View\Page as PageView;
use Lightning\Model\User as UserModel;
use Source\Model\Message;

/**
 * A contact page handler.
 *
 * @package Lightning\Pages
 */
class Contact extends PageView {

    protected $page = 'contact';
    protected $nav = 'contact';

    /**
     * @var UserModel
     */
    protected $user;

    /**
     * @var array
     */
    protected $settings;

    public function __construct() {
        parent::__construct();
        Form::requiresToken();
    }

    protected function hasAccess() {
        return true;
    }

    /**
     * Send a posted contact request to the site admin.
     */
    public function post() {
        $this->settings = Configuration::get('contact');

        // Check captcha if required.
        $request_contact = Request::post('contact', 'boolean');
        if (($this->settings['require_captcha'] === true || ($this->settings['require_captcha'] == 'contact_only' && $request_contact)) && !ReCaptcha::verify()) {
            Messenger::error('You did not correctly enter the captcha code.');
            return $this->get();
        }

        // Make sure the sender's email address is valid.
        if (!$this->getSender()) {
            Messenger::error('Please enter a valid email address.');
            return $this->get();
        }

        // Optin the user.
        if ($list = Request::get('list', 'int')) {
            if (!Message::validateListID($list)) {
                $list = Message::getDefaultListID();
            }
        }
        if (empty($list) && Request::get('optin', 'boolean')) {
            $list = Message::getDefaultListID();
        }
        if (!empty($list)) {
            $this->user->subscribe($list);
        }

        if ($message = Request::post('message', 'int')) {
            $mailer = new Mailer();
            $mailer->sendOne($message, $this->user);
        }

        // Send a message to the site contact.
        if ($this->settings['always_notify'] || ($request_contact || $this->settings['contact'])) {
            $sent = $this->sendMessage();
            if (!$sent) {
                Output::error('Your message could not be sent. Please try again later');
            } else {
                $custom_message = $this->customMessage();
                // Send an email to to have them test for spam.
                if (!empty($this->settings['auto_responder'])) {
                    $auto_responder_mailer = new Mailer();
                    $result = $auto_responder_mailer->sendOne($this->settings['auto_responder'], UserModel::loadByEmail($this->getSender()) ?: new UserModel(array('email' => $this->getSender())));
                    if ($result && $this->settings['spam_test']) {
                        // Set the notice.
                        Navigation::redirect('/message', ['msg' => 'spam_test']);
                    }
                }
                Navigation::redirect('/message', $custom_message ? [] : ['msg' => 'contact_sent']);
            }
        } else {
            $this->customMessage();
        }
    }

    /**
     * Add a custom message from the form input.
     */
    protected function customMessage() {
        if ($this->settings['custom_message'] && $message = Request::post('success')) {
            Messenger::message($message);
            return true;
        }
        return false;
    }

    /**
     * Load the user data and save it into a user entry.
     *
     * @return UserModel|boolean
     */
    protected function getSender() {
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
        if (empty($email)) {
            return false;
        }
        $this->user = UserModel::addUser($email, $name);
        return true;
    }

    public function sendMessage() {
        $mailer = new Mailer();
        foreach ($this->settings['to'] as $to) {
            $mailer->to($to);
        }
        return $mailer
            ->from($this->user->email)
            ->subject($this->settings['subject'])
            ->message($this->getMessageBody())
            ->send();
    }

    /**
     * Create the message body to the site contact.
     *
     * @return string
     */
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
}
