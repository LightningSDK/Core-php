<?php

namespace Lightning\Tools;

use Lightning\Model\Message;
use Lightning\Model\User;

/**
 * Include the PHPMailer
 */
require_once HOME_PATH . '/Lightning/Vendor/PHPMailer/class.phpmailer.php';

class Mailer {
    /**
     * The PHPMailer object.
     *
     * @var \PHPMailer
     */
    protected $mailer;

    /**
     * Whether the from address has been set.
     *
     * @var boolean
     */
    protected $fromSet = false;

    /**
     * A list of users to send the message to in bulk mode.
     *
     * @var array
     */
    protected $users = array();

    /**
     * Whether to output the to addresses as messages are being send.
     *
     * @var boolean
     */
    protected $verbose = false;

    /**
     * The sent from email address.
     *
     * @var string
     */
    protected $from;

    /**
     * The sent from name.
     *
     * @var string
     */
    protected $fromName;

    /**
     * The message to be sent.
     *
     * @var Message
     */
    protected $message;

    /**
     * Construct the mailer object.
     *
     * @param boolean $verbose
     *   Whether to output email addresses as messages are sent.
     */
    public function __construct($verbose = false) {
        $this->mailer = new \PHPMailer(true);
        $this->mailer->Sender = Configuration::get('mailer.bounce_address');
        $this->verbose = $verbose;
        Messenger::setVerbose(true);
    }

    /**
     * Clear all the current to and from addresses.
     */
    public function clearAddresses() {
        $this->mailer->ClearAddresses();
        $this->fromSet = false;
    }

    /**
     * Add a from address.
     *
     * @param string $email
     *   The from email address.
     * @param string $name
     *   The from name.
     *
     * @return Mailer
     *   Returns itself for method chaining.
     */
    public function from($email, $name = null) {
        try {
            $this->mailer->AddReplyTo($email, $name);
            $this->mailer->SetFrom($email, $name);
            $this->mailer->AddReplyTo($email, $name);
            $this->fromSet = true;
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
        }
        return $this;
    }

    /**
     * Add a to address.
     *
     * @param string $email
     *   The to address.
     * @param string $name
     *   The to name.
     *
     * @return Mailer
     *   Returns itself for method chaining.
     */
    public function to($email, $name = null) {
        try {
            $this->mailer->AddAddress($email, $name);
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
        }
        return $this;
    }

    /**
     * Set the message subject.
     *
     * @param string $subject
     *   The subject.
     *
     * @return Mailer
     *   Returns itself for method chaining.
     */
    public function subject($subject) {
        $this->mailer->Subject = $subject;
        return $this;
    }

    /**
     * Set the message body.
     *
     * @param string $message
     *   The message body.
     *
     * @return Mailer
     *   Returns itself for method chaining.
     */
    public function message($message) {
        $this->mailer->MsgHTML($message);
        return $this;
    }

    /**
     * Send the current single message.
     *
     * @return boolean
     *   Whether the message was successful.
     */
    public function send() {
        // Set the default from name if it wasn't set.
        if (!$this->fromSet) {
            $this->from(
                Configuration::get('site.mail_from'),
                Configuration::get('site.mail_from_name')
            );
        }

        // Send the message.
        try {
            return $this->mailer->send();
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
            return false;
        }
    }

    /**
     * Load the test users into the user array.
     */
    protected function loadTestUsers() {
        // Load the test users.
        $users = Configuration::get('mailer.test');
        if (empty($users)) {
            $this->users = array();
        } else {
            $this->users = Database::getInstance()->selectAll('user', array('email' => array('IN', $users)));
        }

        // Load the spam test users.
        $spam_test_from = Configuration::get('spam_test_from');
        foreach (Configuration::get('mailer.spam_test') as $spam_test) {
            $this->users[] = array(
                'email' => $spam_test,
                'first' => 'Spam',
                'last' => 'Test',
                'from' => $spam_test_from,
                'user_id' => 0,
                'salt' => 'na',
            );
        }
    }

    /**
     * Send a bulk message to all users, limited only by message criteria.
     *
     * @param integer $message_id
     *   The ID of the message.
     * @param boolean $test
     *   Whether to just sent a test message.
     */
    function sendBulk($message_id, $test = false){
        $this->message = new Message($message_id);

        $this->from(
            Configuration::get('mailer.mail_from') ?: Configuration::get('site.mail_from'),
            Configuration::get('mailer.mail_from_name') ?: Configuration::get('site.mail_from_name')
        );

        if ($test) {
            $this->message->setTest(true);
            $this->loadTestUsers();
        } else {
            $this->users = $this->message->getUsers();
        }

        if ($this->verbose) {
            echo 'Sending' . ($test ? 'Test' : 'Real') . " Email<br>\n";
        }
        $this->sendToList();

        if ($this->verbose) {
            echo "Test complete";
        }
    }

    /**
     * Send a single email to a single user.
     *
     * @param int $message_id
     *   The message id.
     * @param User $user
     *   The user object to send to.
     */
    function sendOne($message_id, $user) {
        $this->message = new Message($message_id);
        $this->to($user->email, $user->first . ' ' . $user->last);
        $this->message->setUser($user);
        $this->subject($this->message->getSubject());
        $this->message($this->message->getMessage());
        $this->send();
    }

    /**
     * Send the current message to the current list of users.
     */
    protected function sendToList() {
        foreach($this->users as $user){
            if ($this->verbose) {
                echo $user['email'] . "<br>\n";
            }
            // Send message.
            $this->to($user['email'], $user['first'] . ' ' . $user['last']);
            $from = !empty($user['from']) ? $user['from'] : $this->from;
            $from_name = !empty($user['from_name']) ? $user['from_name'] : $this->fromName;
            $this->from($from, $from_name);
            $this->message->setUser(new User($user));
            $this->subject($this->message->getSubject());
            $this->message($this->message->getMessage());
            $this->send();
            $this->mailer->ClearAddresses();
        }
    }
}
