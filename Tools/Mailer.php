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
     * A list of custom variables to be supplied to the message.
     *
     * @var array
     */
    protected $customVariables = array();

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
     * The number of users that the message was sent to.
     *
     * @var integer
     */
    protected $sentCount = 0;

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
    }

    /**
     * Set the value for a custom variable.
     *
     * @param string $var
     *   The variable name found in the email template.
     * @param string $value
     *   The replacement value.
     */
    public function setCustomVariable($var, $value) {
        $this->customVariables[$var] = $value;
    }

    /**
     * Reset all custom variables to the supplied list.
     *
     * @param array $values
     *   A list of variable values keyed by variable names.
     */
    public function resetCustomVariables($values = array()) {
        $this->customVariables = $values;
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
        $this->from = $email;
        $this->fromName = $name;
        try {
            $this->mailer->AddReplyTo($email, $name);
            $this->mailer->SetFrom($email, $name);
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
    public function sendMessage() {
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
        $spam_test_emails = Configuration::get('mailer.spam_test');
        if (is_array($spam_test_emails)) {
            foreach ($spam_test_emails as $spam_test) {
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
    }

    /**
     * Send a bulk message to all users, limited only by message criteria.
     *
     * @param integer $message_id
     *   The ID of the message.
     * @param boolean $test
     *   Whether to just sent a test message.
     * @param boolean $auto
     *   Whether this is called as an automatic mailer.
     *
     * @return integer
     *   The number of users the message was sent to.
     */
    function sendBulk($message_id, $test = false, $auto = false){
        $this->message = new Message($message_id, true, $auto);

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
            echo 'Sending ' . ($test ? 'Test' : 'Real') . " Email<br>\n";
        }
        $this->sendToList();

        if ($this->verbose) {
            echo "Test complete";
        }

        return $this->sentCount;
    }

    /**
     * Sends a single email to a single user.
     * It sends a message loaded from db
     *
     * @param int $message_id
     *   The message id.
     * @param User $user
     *   The user object to send to.
     */
    function sendOne($message_id, $user) {
        $this->message = new Message($message_id);
        $this->message->setUser($user);
        $this->message->resetCustomVariables($this->customVariables);
        $this->to($user->email, $user->first . ' ' . $user->last);
        $this->message->setDefaultVars();
        $this->subject($this->message->getSubject());
        $this->message($this->message->getMessage());
        $this->sendMessage();
        Tracker::trackEvent('Email Sent', $message_id, $user->id);
    }

    /**
     * Send a custom message created with chainable methods like to(), subject(),
     * etc.
     */
    public function send() {
        
        // Need to create a Message object to use a template
        $this->message = new Message(NULL, FALSE);
        $this->message->resetCustomVariables($this->customVariables);
        
        // Assuming the to address is the only one
        $to = $this->mailer->getToAddresses();
        $toName = $to[0][1];
        
        // Set custom variables
        $vars = [
            'FULL_NAME'     => $toName,
            'CONTENT_BODY'  => $this->mailer->Body,
            'SUBJECT'       => $this->mailer->Subject,
        ];
        $this->message->setDefaultVars($vars);
        
        // Set subject and message body. They are applied to a template already
        // TODO: If the message chain is called twice (eg, creating the mail, setting the to address,
        // setting the subject, sending, setting another to address, sending again, this could cause
        // the subject and message to nest recursively, or not render correctly the second time around.
        $this->subject($this->message->getSubject());
        $this->message($this->message->getMessage());
        
        // Actual send
        return $this->sendMessage();
   }

    /**
     * Send the current message to the current list of users.
     */
    protected function sendToList() {
        $this->sentCount = 0;
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
            $this->message->setDefaultVars();
            $this->subject($this->message->getSubject());
            $this->message($this->message->getMessage());
            if ($this->sendMessage()) {
                $this->sentCount ++;
            }
            $this->mailer->ClearAddresses();
            Tracker::trackEvent('Email Sent', $this->message->id, !empty($user['user_id']) ? $user['user_id'] : 0);
        }
    }
}
