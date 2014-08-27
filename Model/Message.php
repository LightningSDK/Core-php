<?php
/**
 * @file
 * Contains Lightning\Model\Message
 */

namespace Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;

/**
 * A model of the mailing system message.
 *
 * @package Lightning\Model
 */
class Message {

    /**
     * The formatted message contents.
     *
     * @var array
     */
    protected $formatted_message = array();

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
     * Custom variables to replace in the message.
     *
     * @var array
     */
    protected $custom_variables = array();

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
        return 'You&rsquo;re receiving this as you previously enquired with us about a product or business or you&rsquo;re a friend through social media. To stop receiving these emails, '
            ."<a href='http://" . Configuration::get('site.domain') . "/user.php?action=unsubscribe&email=" . urlencode($this->user['email']) . "&code=".User::unsubscribeKey($this->user['user_id'], $this->user['email'])
            ."'>click here</a>";

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
        // REPLACE CUSTOM VARIABLES
        foreach($this->custom_variables as $cv => $cvv){
            $source = str_replace("{".$cv."}", $cvv, $source);
        }

        // STANDARD VARIABLES
        $source = str_replace("{USER_ID}", $this->user['user_id'], $source);
        $source = str_replace("{MESSAGE_ID}", $this->message['message_id'], $source);
        $source = str_replace("{FULL_NAME}", (!empty($this->user['first']) ? $this->user['first'] . ' ' . $this->user['last'] : "friend"), $source);
        $source = str_replace("{URL_KEY}", User::urlKey($this->user['user_id'], $this->user['salt']), $source);
        $source = str_replace("{EMAIL}", $this->user['email'], $source);

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
        // TODO: this can be computed once instead of in this loop.
        $message = str_replace('{CONTENT_BODY}', $this->message['body'], $this->template['body']);
        $message = $this->replaceVariables($message);

        // Is the unsubscribe location specified?
        // TODO: This should be inserted by default after the message and before the template.
        $message = str_replace('{UNSUBSCRIBE}', $this->unsubscribe ? $this->getUnsubscribeString() : '', $message);

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
