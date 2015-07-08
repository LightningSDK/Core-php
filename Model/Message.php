<?php
/**
 * @file
 * Contains Lightning\Model\Message
 */

namespace Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Language;
use Lightning\Tools\Messenger;
use Lightning\Tools\Tracker;

/**
 * A model of the mailing system message.
 *
 * @package Lightning\Model
 */
class Message extends Object {

    const PRIMARY_KEY = 'message_id';

    /**
     * Whether this is being sent by the auto mailer.
     *
     * @var boolean
     */
    protected $auto = false;

    /**
     * A list of criteria associated with the message.
     *
     * @var
     */
    protected $criteria;

    /**
     * Custom variables to replace in the message.
     *
     * @var array
     */
    protected $customVariables = array();

    /**
     * The combined template and message without variables filled.
     *
     * @var string
     */
    protected $combinedMessageTemplate;

    /**
     * Default variables to replace in the message.
     *
     * @var array
     */
    protected $defaultVariables = array();

    /**
     * Custom variables to replace in the message, read from templates.
     *
     * @var array
     */
    protected $internalCustomVariables = array();

    /**
     * The formatted message contents.
     *
     * @var array
     */
    protected $formattedMessage = array();

    /**
     * The mailing lists that will receive the message.
     *
     * @var array
     */
    protected $lists = null;

    /**
     * The tracker ID for a sent message.
     *
     * @var integer
     */
    protected static $message_sent_id;

    /**
     * The name to use if the users name is not set.
     *
     * @var string
     */
    protected $default_name = 'friend';

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
     * Loads a message either from the database or create it from scratch for
     * custom messages.
     *
     * @param integer $message_id
     *   The ID of the message to load.
     * @param boolean $unsubscribe
     *   Whether to include the ubsubscribe link when sending.
     * @param boolean $auto
     *   Whether this is called as an automatic mailer.
     */
    public function __construct($message_id = null, $unsubscribe = true, $auto = true) {
        $this->auto = $auto;
        $this->__data = Database::getInstance()->selectRow('message', array('message_id' => $message_id));
        $this->loadTemplate();
        $this->unsubscribe = $unsubscribe;

        if (empty(self::$message_sent_id)) {
            self::$message_sent_id = Tracker::getTrackerId('Email Sent');
        }

        if ($default_name_settings = Configuration::get('mailer.default_name')) {
            $this->default_name = $default_name_settings;
        }

        $this->setCombinedMessageTemplate();
        
        $this->loadVariablesFromTemplate();
    }

    /**
     * Sets a combined message template.
     * For custom message it means defining it as template body
     * For db message it makes some replaces
     */
    protected function setCombinedMessageTemplate() {
        if (empty($this->__data)) {
            $this->combinedMessageTemplate = $this->template['body'] . '{TRACKING_IMAGE}';
        } elseif (!strpos($this->template['body'], '{UNSUBSCRIBE}')) {
            $this->combinedMessageTemplate = str_replace('{CONTENT_BODY}', $this->body . '{UNSUBSCRIBE}', $this->template['body']) . '{TRACKING_IMAGE}';
        } else {
            $this->combinedMessageTemplate = str_replace('{CONTENT_BODY}', $this->body, $this->template['body']) . '{TRACKING_IMAGE}';
        }
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
     * Parse the template for {VARIABLE=VALUE} tags.
     */
    protected function loadVariablesFromTemplate() {
        $set_variable = array();
        preg_match_all('/{([a-z_]+)=(.*)}/imU', $this->combinedMessageTemplate, $set_variable);
        foreach ($set_variable[1] as $index => $var) {
            // Save the variable value.
            $this->internalCustomVariables[$var] = $set_variable[2][$index];
            // Remove the setting tag.
            $this->combinedMessageTemplate = str_replace($set_variable[0][$index], '', $this->combinedMessageTemplate);
        }
    }

    /**
     * Loads template depending on message type: custom or database
     */
    protected function loadTemplate() {
        if (!empty($this->message_id)) {
            $this->loadTemplateByMessage();
        } else {
            $this->loadTemplateFromConfig();
        }
        
    }

    /**
     * Custom message has a template which determined in configuration.
     * It gets it, checks and applies a message to it.
     */
    protected function loadTemplateFromConfig() {
        
        // check configuration
        $template_id = Configuration::get('mailer.mail_template');
        
        // set template from config or default template
        $this->template = Database::getInstance()->selectRow(
            'message_template',
            ['template_id' => $template_id]
        );
        
        // If there's no such template or it's not configured set the default template
        if (empty($this->template)) {
            $this->setDefaultTemplate();
        }
    }
    
    /**
     * The default template is used when there was no chance to define a template
     * for a message.
     * This function creates it.
     */
    protected function setDefaultTemplate() {
        $this->template = array(
            'subject' => 'A message from ' . Configuration::get('site.name'),
            'body' => '{CONTENT_BODY}',
        );
    }

    /**
     * Loads the template from the database based on the message.
     */
    protected function loadTemplateByMessage() {
        if ($this->template['template_id'] != $this->template_id) {
            if ($this->template_id > 0) {
                $this->template = Database::getInstance()->selectRow(
                    'message_template',
                    array('template_id' => $this->template_id)
                );
            } else {
                $this->setDefaultTemplate();
            }
        }
    }


    protected function loadLists() {
        if ($this->lists === null) {
            $this->lists = Database::getInstance()->selectColumn('message_message_list', 'message_list_id', array('message_id' => $this->message_id));
        }
    }

    /**
     * Loads sending criteria and specific message variables.
     */
    protected function loadCriteria() {
        if ($this->criteria === null) {
            $this->criteria = Database::getInstance()->selectAll(
                array(
                    'from' => 'message_message_criteria',
                    'join' => array(
                        'LEFT JOIN',
                        'message_criteria',
                        'USING (message_criteria_id)',
                    ),
                ),
                array(
                    'message_id' => $this->message_id,
                )
            );
        }
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
                '{LINK}' => $this->user->getUnsubscribeLink()
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
        // Replace variables.
        foreach($this->customVariables + $this->internalCustomVariables + $this->defaultVariables as $cv => $cvv) {
            // Replace simple variables as a string.
            $source = str_replace('{' . $cv . '}', $cvv, $source);
            // Some curly brackets might be escaped if they are links.
            $source = str_replace('%7B' . $cv . '%7D', $cvv, $source);
        }

        // Replace conditions.
        $conditions = array();
        $conditional_search = '/{IF ([a-z_]+)}(.*){ENDIF \1}/imU';
        preg_match_all($conditional_search, $source, $conditions);
        foreach ($conditions[1] as $key => $var) {
            if (!empty($this->customVariables[$var]) || !empty($this->internalCustomVariables[$var])) {
                $source = preg_replace($conditional_search, $conditions[2][$key], $source);
            } else {
                $source = preg_replace($conditional_search, '', $source);
            }
        }

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
        $subject = !empty($this->subject) ? $this->subject : $this->template['subject'];
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
     * Sets default variables for db and chainable messages
     * 
     * @param array $vars
     *   Variables to set for chainable messages
     */
    public function setDefaultVars($vars = null) {
        /*
         * If there's no 'message' variable set, it's a custom message, 
         * so we don't replace any variables except custom ones.
         */
        
        if (!empty($this->message_id)) {

            $tracking_image = Tracker::getTrackerImage('Email Opened', $this->message_id, $this->user->id);
            
            // Replace standard variables.
            $this->defaultVariables = [
                'USER_ID' => $this->user->id,
                'MESSAGE_ID' => $this->message_id,
                'FULL_NAME' => (!empty($this->user->first) ? $this->user->fullName() : $this->default_name),
                'URL_KEY' => !empty($this->user->id) ? User::urlKey($this->user->id, $this->user->salt) : '',
                'EMAIL' => $this->user->email,

                // Add the unsubscribe link.
                'UNSUBSCRIBE' => $this->unsubscribe && !empty($this->user->user_id) ? $this->getUnsubscribeString() : '',

                // Add the tracking image to the bottom of the email.
                'TRACKING_IMAGE' => $tracking_image,
            ];
        } else {
            $this->defaultVariables = [
                // Add the unsubscribe link.
                'UNSUBSCRIBE' => $this->unsubscribe && !empty($this->user->user_id) ? $this->getUnsubscribeString() : '',
                'TRACKING_IMAGE' => '',
            ];
            if (!empty($vars)) {
                $this->defaultVariables += $vars;
            }
        }
    }
    
    /**
     * Get the user query for users who will receive this message.
     *
     * @return array
     *   An array of users.
     */
    protected function getUsersQuery() {
        $this->loadLists();
        if (empty($this->lists)) {
            Messenger::error('Your message does not have any mailing lists selected.');
            return array('table' => 'user', 'where' => array('false' => array('expression' => 'false')));
        }

        // Start with a list of users in the messages selected lists.
        $table = array(
            'from' => 'message_list_user',
            'join' => array(array(
                'JOIN',
                'user',
                'ON user.user_id = message_list_user.user_id',
            )),
        );
        $where = array('message_list_id' => array('IN', $this->lists));

        // Make sure the message is never resent.
        if ($this->auto || !empty($this->never_resend)) {
            $table['join'][] = array(
                'LEFT JOIN',
                'tracker_event',
                'ON tracker_event.user_id = user.user_id AND tracker_event.tracker_id = ' . self::$message_sent_id . ' AND tracker_event.sub_id = ' . $this->message_id,
            );
            $where['tracker_event.user_id'] = null;
        }

        // Make sure the user matches a criteria.
        $this->loadCriteria();
        foreach ($this->criteria as $criteria) {
            $field_values = json_decode($criteria['field_values'], true);
            if (!empty($criteria['join'])) {
                if ($c_table = json_decode($criteria['join'], true)) {
                    // The entry is a full join array.
                    $this->replaceCriteriaVariables($c_table, $field_values);
                    $table['join'][] = $c_table;
                } else {
                    // The entry is just a table name.
                    $table['join'][] = array(
                        'LEFT JOIN',
                        $criteria['join'],
                        'ON ' . $criteria['join'] . '.user_id = user.user_id',
                    );
                }
            }
            if ($test = json_decode($criteria['test'], true)) {
                $this->replaceCriteriaVariables($test, $field_values);
                $where[] = $test;
            }
        }

        return array('table' => $table, 'where' => $where);
    }

    protected function replaceCriteriaVariables(&$test, $variables) {
        array_walk_recursive($test, function(&$item) use ($variables) {
            if (is_string($item)) {
                foreach ($variables as $var => $value) {
                    $item = preg_replace('/{' . $var . '}/', $value, $item);
                }
            }
        });
    }

    /**
     * Gets a list of users from the database.
     *
     * @return \PDOStatement
     *   An object to iterate all the users who will receive the email.
     */
    public function getUsers() {
        $query = $this->getUsersQuery();
        return Database::getInstance()->select($query['table'], $query['where'], array('uid' => array('expression' => 'DISTINCT(user.user_id)'), 'user.*'));
    }

    /**
     * Get a count of how many users will receive this message.
     *
     * @return integer
     *   The number of users.
     */
    public function getUsersCount() {
        $query = $this->getUsersQuery();
        return Database::getInstance()->count($query['table'], $query['where'], 'DISTINCT(user.user_id)');
    }
    
    public function setTemplate($template_id) {
        $this->template = Database::getInstance()->selectRow(
            'message_template',
            array('template_id' => $template_id)
        );
    }
}
