<?php

namespace Lightning\Tools;

use Exception;
use Lightning\Model\Message;
use Lightning\Model\User;
use Lightning\Model\Tracker as TrackerModel;

/**
 * Include the PHPMailer
 */
require_once HOME_PATH . '/Lightning/Vendor/PHPMailer/class.phpmailer.php';

class Mailer {

    /**
     * If debug is enabled, this will only sent emails to the site admin.
     * This is for testing environments.
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * Whether the message and subject have been built.
     *
     * @var boolean
     */
    protected $built = false;

    /**
     * A list of custom variables to be supplied to the message.
     *
     * @var array
     */
    protected $customVariables = [];

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
     * Whether the reply-to has been explicitly set.
     *
     * @var boolean
     */
    protected $replyToSet = false;

    /**
     * A list of users to send the message to in bulk mode.
     *
     * @var array
     */
    protected $users = [];

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

    protected $limit = 0;
    protected $random = false;

    /**
     * Construct the mailer object.
     *
     * @param boolean $verbose
     *   Whether to output email addresses as messages are sent.
     */
    public function __construct($verbose = false) {
        $this->debug = Configuration::get('debug', false);
        $this->mailer = new \PHPMailer(true);
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Sender = Configuration::get('mailer.bounce_address');
        $this->verbose = $verbose;
        if ($smtpHost = Configuration::get('mailer.smtp')) {
            require_once HOME_PATH . '/Lightning/Vendor/PHPMailer/class.smtp.php';
            $this->mailer->Mailer = 'smtp';
            $this->mailer->Host = $smtpHost;
        }

        if ($dkim_key = Configuration::get('mailer.dkim_key')) {
            $this->mailer->DKIM_domain = Configuration::get('mailer.dkim_domain');
            $this->mailer->DKIM_private = $dkim_key;
            $this->mailer->DKIM_selector = Configuration::get('mailer.dkim_selector');
            $this->mailer->DKIM_passphrase = '';
        }
    }

    public function setRandom($random) {
        $this->random = $random;
    }

    public function setLimit($limit) {
        $this->limit = $limit;
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
    public function resetCustomVariables($values = []) {
        $this->customVariables = $values;
    }

    /**
     * Clear all the current to and from addresses.
     */
    public function clearAddresses() {
        $this->mailer->ClearAddresses();
        return $this;
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
        $this->mailer->DKIM_identity = $this->from = $email;
        $this->fromName = $name;
        try {
            if (!$this->replyToSet) {
                $this->mailer->AddReplyTo($email, $name);
            }
            $this->mailer->SetFrom($email, $name);
            $this->fromSet = true;
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
        }
        return $this;
    }

    /**
     * Set the reply to address.
     *
     * @param string $email
     *   The email address
     * @param string $name
     *   The address name.
     *
     * @return Mailer
     *   Returns itself for method chaining.
     */
    public function replyTo($email, $name = null) {
        try {
            $this->mailer->AddReplyTo($email, $name);
            $this->replyToSet = true;
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
        }
        return $this;
    }

    /**
     * Add a to address.
     *
     * @param User|string $to
     *   The to address.
     * @param string $name
     *   The to name.
     *
     * @return Mailer
     *   Returns itself for method chaining.
     */
    public function to($to, $name = null) {
        try {
            if (is_object($to)) {
                // This is a user account.
                $name = $to->fullName();
                $email = $to->email;
            } else {
                $email = $to;
            }
            $this->mailer->AddAddress($email, $name);
        } catch (\Exception $e) {
            Messenger::error($e->getMessage());
        }
        return $this;
    }

    /**
     * Set the recipient user and the default variables.
     *
     * @param User $user
     *   The user to receive the email.
     */
    public function setUser($user) {
        $this->message->setUser($user);
        $this->message->setDefaultVars();
        $this->clearAddresses();
        $this->to($user->email, $user->first . ' ' . $user->last);
        $this->built = false;
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
        $this->mailer->MsgHTML($message, '', true);
        return $this;
    }

    protected function rebuildMessage() {
        $this->subject($this->message->getSubject());
        $this->message($this->message->getMessage());
    }

    /**
     * Load a message from the database.
     *
     * @param $message_id
     *
     * @throws Exception
     */
    public function loadMessage($message_id) {
        // Only load if this is a different message than was used before.
        if (empty($this->message->id) || $this->message->id != $message_id) {
            $this->message = Message::loadByID($message_id);
            $this->rebuildMessage();
        }
    }

    /**
     * Set the message to a message object.
     *
     * @param Message $message
     */
    public function setMessage($message) {
        $this->message = $message;
        $this->rebuildMessage();
    }

    /**
     * Build and send the current single message.
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

        if ($this->message && !$this->built) {
            // Rebuild with the new custom variables.
            $this->message->resetCustomVariables($this->customVariables);
            $this->rebuildMessage();
            $this->built = true;
        }

        // Send the message.
        try {
            if ($this->debug) {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress(Configuration::get('contact.to')[0]);
            }
            if (($success = $this->mailer->send())
            && $this->message && $this->message->getUser()) {
                TrackerModel::loadOrCreateByName('Email Sent', TrackerModel::EMAIL)
                    ->track($this->message->id, $this->message->getUser()->id);
            }
            return $success;
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
            $this->users = [];
        } else {
            $this->users = Database::getInstance()->selectAll('user', ['email' => ['IN', $users]]);
        }

        // Load the spam test users.
        $spam_test_from = Configuration::get('spam_test_from');
        $spam_test_emails = Configuration::get('mailer.spam_test');
        if (is_array($spam_test_emails)) {
            foreach ($spam_test_emails as $spam_test) {
                $this->users[] = [
                    'email' => $spam_test,
                    'first' => 'Spam',
                    'last' => 'Test',
                    'from' => $spam_test_from,
                    'user_id' => 0,
                    'salt' => 'na',
                ];
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
     *
     * @throws Exception
     */
    public function sendBulk($message_id, $test = false, $auto = false) {
        $this->message = Message::loadByID($message_id, true, $auto);

        $this->from(
            Configuration::get('mailer.mail_from') ?: Configuration::get('site.mail_from'),
            Configuration::get('mailer.mail_from_name') ?: Configuration::get('site.mail_from_name')
        );

        if ($test) {
            $this->message->setTest();
            $this->loadTestUsers();
        } else {
            $this->message->setLimit($this->limit);
            $this->message->setRandom($this->random);
            $this->users = $this->message->getUsers();
        }

        if ($this->verbose) {
            echo 'Sending ' . ($test ? 'Test' : 'Real') . " Email ";
        }
        $this->sendToList();

        if ($this->verbose) {
            echo ($test ? 'Test' : 'Mailing') . ' complete';
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
     *
     * @return boolean
     *   Whether the message was sent successfully.
     *
     * @throws Exception
     *   If the user can not be found.
     */
    public function sendOne($message_id, $user) {
        $this->built = false;
        if (is_string($user)) {
            $user = User::addUser($user);
        }
        if (empty($user)) {
            throw new Exception('Invalid User');
        }
        $this->clearAddresses();
        $this->loadMessage($message_id);
        $this->message->resetCustomVariables($this->customVariables);
        $this->message->setUser($user);
        $this->message->setDefaultVars();
        $this->to($user);
        return $this->sendMessage();
    }

    /**
     * Send a custom message created with chainable methods like to(), subject(),
     * etc.
     */
    public function send() {

        // If we are sending a message object, it needs to be built.
        if ($this->message) {
            // Need to create a Message object to use a template
            $this->message->resetCustomVariables($this->customVariables);

            // Set subject and message body. They are applied to a template already
            // TODO: If the message chain is called twice (eg, creating the mail, setting the to address,
            // setting the subject, sending, setting another to address, sending again, this could cause
            // the subject and message to nest recursively, or not render correctly the second time around.
            $this->rebuildMessage();
        }

        // Actual send
        return $this->sendMessage();
   }

    /**
     * Send the current message to the current list of users.
     */
    protected function sendToList() {
        $this->sentCount = 0;
        foreach($this->users as $user) {
            if ($this->verbose && $this->sentCount % 100 == 0) {
                set_time_limit(60);
                echo '. ';
            }
            // Send message.
            $this->to($user['email'], $user['first'] . ' ' . $user['last']);
            $from = !empty($user['from']) ? $user['from'] : $this->from;
            $from_name = !empty($user['from_name']) ? $user['from_name'] : $this->fromName;
            $this->from($from, $from_name);
            $this->message->setUser(new User($user));
            $this->message->setDefaultVars();
            $this->rebuildMessage();
            if ($this->sendMessage()) {
                $this->sentCount ++;
            }
            $this->mailer->ClearAddresses();
        }
        echo "\n\n";
    }

    public function addStringAttachment($data, $filename, $encoding = 'base64', $content_type = '', $disposition = 'attachment') {
        $this->mailer->addStringAttachment($data, $filename, $encoding, $content_type, $disposition);
    }
}
