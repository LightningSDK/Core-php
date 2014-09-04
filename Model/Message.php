<?php
/**
 * @file
 * Contains Lightning\Model\Message
 */

namespace Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Language;
use Lightning\Tools\Tracker;

/**
 * A model of the mailing system message.
 *
 * @package Lightning\Model
 */
class Message {

    /**
     * Custom variables to replace in the message.
     *
     * @var array
     */
    protected $customVariables = array();

    /**
     * The formatted message contents.
     *
     * @var array
     */
    protected $formattedMessage = array();

    /**
     * The message data from the database.
     *
     * @var array
     */
    protected $message;

    /**
     * The template data from the database.
     *
     * @var array
     */
    protected $template;

    /**
     * Whether this message should be processed in test mode.
     *
     * @var boolean
     */
    protected $test;

    /**
     * Whether to include the unsubscribe link.
     *
     * @var boolean
     */
    protected $unsubscribe = true;

    /**
     * The user currently being sent to.
     *
     * @var User
     */
    protected $user;

    /**
     * Load a message from the database.
     *
     * @param integer $message_id
     *   The ID of the message to load.
     * @param boolean $unsubscribe
     *   Whether to include the ubsubscribe link when sending.
     */
    public function __construct($message_id, $unsubscribe = true) {
        $this->message = Database::getInstance()->selectRow('message', array('message_id' => $message_id));
        $this->loadTemplate();
        $this->loadCriteria();
        $this->unsubscribe = $unsubscribe;
        if (
            $this->unsubscribe
            && !strstr($this->message['body'], '{UNSUBSCRIBE}')
            && !strstr($this->template['body'], '{UNSUBSCRIBE}')
        ) {
            $this->combinedMessageTemplate = str_replace('{CONTENT_BODY}', $this->message['body'] . '{UNSUBSCRIBE}', $this->template['body']);
        } else {
            $this->combinedMessageTemplate = str_replace('{CONTENT_BODY}', $this->message['body'], $this->template['body']);
        }
    }

    /**
     * Loads the template from the database based on the message.
     */
    protected function loadTemplate(){
        if($this->template['template_id'] != $this->message['template_id']){
            if($this->message['template_id'] > 0) {
                $this->template = Database::getInstance()->selectRow(
                    'message_template',
                    array('template_id' => $this->message['template_id'])
                );
            } else {
                $this->template = array(
                    "subject"=>"A message from " . Configuration::get('site.name'),
                    "body"=>"{CONTENT_BODY}"
                );
            }
        }
    }

    /**
     * Loads sending criteria.
     *
     * @todo
     *   This should load conditions on the message to get a limited list of users.
     */
    protected function loadCriteria() {

    }

    /**
     * Sets the test mode to true.
     */
    public function setTest() {
        $this->test = true;
    }

    /**
     * Sets the current user.
     *
     * @param User $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    /**
     * Get the unsubscribe string for the current user.
     *
     * @return string
     *   Outputs the unsubscribe string.
     */
    protected function getUnsubscribeString() {
        return Language::getInstance()->translate('unsubscribe', array(
                '{UNSUBSCRIBE}' => $this->user->getUnsubscribeLink()
            )
        );
    }

    /**
     * Replace variables in the supplied content.
     *
     * @param string $source
     *   The source content.
     *
     * @return string
     *   The content with replaced variables.
     */
    public function replaceVariables($source) {
        // Replace custom variables.
        foreach($this->customVariables as $cv => $cvv){
            $source = str_replace("{".$cv."}", $cvv, $source);
        }

        // Replace standard variables.
        $source = str_replace("{USER_ID}", $this->user->details['user_id'], $source);
        $source = str_replace("{MESSAGE_ID}", $this->message['message_id'], $source);
        $source = str_replace("{FULL_NAME}", (!empty($this->user->details['first']) ? $this->user->details['first'] . ' ' . $this->user->details['last'] : "friend"), $source);
        $source = str_replace("{URL_KEY}", User::urlKey($this->user->details['user_id'], $this->user->details['salt']), $source);
        $source = str_replace("{EMAIL}", $this->user->details['email'], $source);

        // Add the unsubscribe link.
        $source = str_replace('{UNSUBSCRIBE}', $this->unsubscribe ? $this->getUnsubscribeString() : '', $source);

        // Add the tracking image to the bottom of the email.
        // TODO: This needs to be moved., it shows up in the subject.
        $source .= Tracker::getTrackerImage('Message Opened', $this->message['message_id'], $this->user->details['user_id']);

        return $source;
    }

    /**
     * Get the message subject with variables replaced.
     *
     * @return string
     *   The message subject.
     */
    public function getSubject() {
        // Start by combining subject and template.
        $subject = !empty($this->message['subject']) ? $this->message['subject'] : $this->template['subject'];
        $subject = $this->replaceVariables($subject);

        return ($this->test ? 'TEST ' : '') . $subject;
    }

    /**
     * Get the message body with variables replaced.
     *
     * @return string
     *   The message body.
     */
    public function getMessage() {
        // Start by combining message and template.
        $message = $this->replaceVariables($this->combinedMessageTemplate);

        return $message;
    }

    /**
     * Get the user query for users who will receive this message.
     *
     * @return array
     *   An array of users.
     */
    protected function getUsersQuery() {
        $table = array(
            'from' => 'user',
        );
        $where = array(
            'active' => 1,
        );

        return array('table' => $table, 'where' => $where);
    }

    /**
     * Gets a list of users from the database.
     *
     * @return \PDOStatement
     *   An object to iterate all the users who will receive the email.
     */
    public function getUsers() {
        $query = $this->getUsersQuery();
        return Database::getInstance()->select($query['table'], $query['where']);
    }

    /**
     * Get a count of how many users will receive this message.
     *
     * @return integer
     *   The number of users.
     */
    public function getUsersCount() {
        $query = $this->getUsersQuery();
        return Database::getInstance()->count($query['table'], $query['where']);
    }
}
