<?php

namespace Lightning\Tools;

use Lightning\Model\Message;
use Lightning\Model\User;

require_once HOME_PATH . '/Lightning/Vendor/PHPMailer/class.phpmailer.php';

class Mailer {
    protected $mailer;
    protected $fromSet = false;
    protected $users = array();
    protected $verbose = false;
    protected $from;
    protected $fromName;

    /**
     * @var Message
     */
    protected $message;

    public function __construct($verbose = false) {
        $this->mail = new \PHPMailer(true);
        $this->mail->Sender = Configuration::get('mailer.bounce_address');
        $this->verbose = $verbose;
        Messenger::setVerbose(true);
    }

    public function clearAddresses() {
        $this->mail->ClearAddresses();
        $this->fromSet = false;
    }

    public function from($email, $name = null) {
        try {
            $this->mail->AddReplyTo($email, $name);
            $this->mail->SetFrom($email, $name);
            $this->mail->AddReplyTo($email, $name);
            $this->fromSet = true;
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
        }
        return $this;
    }

    public function to($email, $name = null) {
        try {
            $this->mail->AddAddress($email, $name);
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
        }
        return $this;
    }

    public function subject($subject) {
        $this->mail->Subject = $subject;
        return $this;
    }

    public function message($message) {
        $this->mail->HTMLBody = $message;
        $this->mail->Body = $message;
        return $this;
    }

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
            return $this->mail->send();
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
            return false;
        }
    }

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

    function sendBulk($id, $test = false){
        $this->message = new Message($id);

        $this->from = Configuration::get('mailer.mail_from') ?: Configuration::get('site.mail_from');
        $this->fromName = Configuration::get('mailer.mail_from_name') ?: Configuration::get('site.mail_from_name');

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
            $this->mail->ClearAddresses();
        }
    }
}
