<?php
/**
 * @file
 * Contains Lightning\Model\Message
 */

namespace Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Language;
use Lightning\View\Field\Time;
use Lightning\View\HTMLEditor\Markup;

/**
 * A model of the mailing system message.
 *
 * @package Lightning\Model
 */
class MessageOverridable extends Object {

    const TABLE = 'message';

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
    protected $customVariables = [];

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
    protected $defaultVariables = [];

    /**
     * Custom variables to replace in the message, read from templates.
     *
     * @var array
     */
    protected $internalCustomVariables = [];

    /**
     * The formatted message contents.
     *
     * @var array
     */
    protected $formattedMessage = [];

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
     * Whether to deliver a random subset.
     *
     * @var boolean
     */
    protected $random = false;

    /**
     * Whether to limit the number of deliveries.
     *
     * @var integer
     */
    protected $limit = 0;

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
     *
     * @return Message
     *
     * @throws \Exception
     */
    public static function loadByID($message_id = null, $unsubscribe = true, $auto = true) {
        $data = Database::getInstance()->selectRow('message', ['message_id' => $message_id]);
        $message = new static($data);
        $message->loadTemplate();
        $message->unsubscribe = $unsubscribe;
        $message->auto = $auto;

        if (empty(self::$message_sent_id)) {
            self::$message_sent_id = Tracker::loadOrCreateByName('Email Sent', Tracker::EMAIL)->id;
        }

        if ($default_name_settings = Configuration::get('mailer.default_name')) {
            $message->default_name = $default_name_settings;
        }

        $message->setCombinedMessageTemplate();

        $message->loadVariablesFromTemplate();

        return $message;
    }

    public function setRandom($random) {
        $this->random = $random;
    }

    public function setLimit($limit) {
        $this->limit = $limit;
    }

    /**
     * Sets a combined message template.
     * For custom message it means defining it as template body
     * For db message it makes some replaces
     */
    protected function setCombinedMessageTemplate() {
        if (!empty($this->template) && strpos($this->template['body'], '{CONTENT_BODY}') !== false) {
            // If a template is loaded, start with the combined body as the template.
            $this->combinedMessageTemplate = str_replace('{CONTENT_BODY}', $this->body, $this->template['body']);
        } else {
            // Otherwise just use this message body.
            $this->combinedMessageTemplate = $this->body;
        }

        // See if there are any missing required tags.
        $additions = '';
        foreach (['UNSUBSCRIBE', 'TRACKING_IMAGE'] as $requirement) {
            if (strpos($this->combinedMessageTemplate, '{' . $requirement . '}') === false) {
                $additions = '{' . $requirement . '}';
            }
        }

        // Add the missing required tags.
        if (!empty($additions) && strpos($this->template['body'], '{CONTENT_BODY}') !== false) {
            $this->combinedMessageTemplate = str_replace('{CONTENT_BODY}', '{CONTENT_BODY}' . $additions, $this->combinedMessageTemplate);
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
    public function resetCustomVariables($values = []) {
        $this->customVariables = $values;
    }

    /**
     * Parse the template for {VARIABLE=VALUE} tags.
     */
    protected function loadVariablesFromTemplate() {
        $set_variable = [];
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
        $this->template = [
            'subject' => 'A message from ' . Configuration::get('site.name'),
            'body' => '{CONTENT_BODY}',
        ];
    }

    /**
     * Loads the template from the database based on the message.
     */
    protected function loadTemplateByMessage() {
        if ($this->template['template_id'] != $this->template_id) {
            if ($this->template_id > 0) {
                $this->template = Database::getInstance()->selectRow(
                    'message_template',
                    ['template_id' => $this->template_id]
                );
            } else {
                $this->setDefaultTemplate();
            }
        }
    }

    /**
     * Load the lists that this message can be sent to.
     */
    protected function loadLists() {
        if ($this->lists === null) {
            if (!empty($this->any_list)) {
                $this->lists = static::getAllLists();
            } else {
                $this->lists = Database::getInstance()->selectColumn(
                    'message_message_list',
                    'message_list_id',
                    ['message_id' => $this->message_id]
                );
            }
        }
    }

    public static function getAllLists() {
        return Database::getInstance()->selectColumn('message_list', 'name', [], 'message_list_id');
    }

    public static function getAllListIDs() {
        return Database::getInstance()->selectColumn('message_list', 'message_list_id');
    }

    public static function validateListID($id) {
        return Database::getInstance()->check('message_list', ['message_list_id' => $id]);
    }

    public static function getListIDByName($name) {
        $db = Database::getInstance();
        $list = $db->selectField('message_list_id', 'message_list', ['name' => $name]);
        if (!$list) {
            $list = $db->insert('message_list', ['name' => $name]);
        }
        return $list;
    }

    public static function getDefaultListID() {
        return static::getListIDByName('Default');
    }

    /**
     * Loads sending criteria and specific message variables.
     */
    protected function loadCriteria() {
        if ($this->criteria === null) {
            $this->criteria = Database::getInstance()->selectAllQuery([
                'from' => 'message_message_criteria',
                'join' => [
                    'left_join' => 'message_criteria',
                    'using' => 'message_criteria_id',
                ],
                'where' => ['message_id' => $this->message_id],
            ]);
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
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Get the unsubscribe string for the current user.
     *
     * @return string
     *   Outputs the unsubscribe string.
     */
    protected function getUnsubscribeString() {
        return Language::translate('unsubscribe', [
                '{LINK}' => $this->user->getUnsubscribeLink()
            ]
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
     *
     * @throws \Exception
     */
    public function replaceVariables($source) {
        // Replace variables.
        $vars = $this->customVariables + $this->internalCustomVariables + $this->defaultVariables;
        return Markup::render($source, $vars);
    }
    
    /**
     * Get the message subject with variables replaced.
     *
     * @return string
     *   The message subject.
     *
     * @throws \Exception
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
     *
     * @throws \Exception
     */
    public function getMessage() {
        // Make sure the combined message is ready.
        if (empty($this->combinedMessageTemplate)) {
            $this->setCombinedMessageTemplate();
        }

        // Start by combining message and template.
        $message = $this->replaceVariables($this->combinedMessageTemplate);

        return $message;
    }

    /**
     * Sets default variables for db and chainable messages
     * 
     * @param array $vars
     *   Variables to set for chainable messages
     *
     * @throws \Exception
     */
    public function setDefaultVars($vars = null) {
        /*
         * If there's no 'message' variable set, it's a custom message, 
         * so we don't replace any variables except custom ones.
         */
        
        if (!empty($this->message_id)) {

            $tracker = Tracker::loadOrCreateByName('Email Opened', Tracker::EMAIL);
            $tracking_image = $tracker->getTrackerImage($this->message_id, $this->user->id);
            
            // Replace standard variables.
            $this->defaultVariables = [
                'MESSAGE_ID' => $this->message_id,
                'URL_KEY' => !empty($this->user->id) ? User::urlKey($this->user->id, $this->user->salt) : '',

                // Add the unsubscribe link.
                'UNSUBSCRIBE' => $this->unsubscribe && !empty($this->user->user_id) ? $this->getUnsubscribeString() : '',

                // Add the tracking image to the bottom of the email.
                'TRACKING_IMAGE' => $tracking_image,
            ];
        } else {
            $this->defaultVariables = [
                'TRACKING_IMAGE' => '',
            ];
            if (!empty($vars)) {
                $this->defaultVariables += $vars;
            }
        }

        if (!empty($this->user)) {
            // Add per user variables.
            $this->defaultVariables += [
                'FULL_NAME' => (!empty($this->user->first) ? $this->user->fullName() : $this->default_name),
                'FIRST_NAME' => (!empty($this->user->first) ? $this->user->first : $this->default_name),
                'LAST_NAME' => $this->user->last,
                'USER_ID' => $this->user->id,
                'EMAIL' => $this->user->email,
                'UNSUBSCRIBE' => $this->unsubscribe && !empty($this->user->user_id) ? $this->getUnsubscribeString() : '',
            ];
        }
    }
    
    /**
     * Get the user query for users who will receive this message.
     *
     * @return array
     *   An array of users.
     *
     * @throws \Exception
     *   If there are no lists for the message.
     */
    protected function getUsersQuery() {

        // The query starts by searching for users on lists.
        $query = [
            'select' => [
                'uid' => ['expression' => 'DISTINCT(user.user_id)'],
                'user.*',
            ],
            'from' => 'message_list_user',
            'join' => [[
                'join' => 'user',
                'on' => ['user.user_id' => ['expression' => 'message_list_user.user_id']],
            ]],
        ];

        // Limit the users to those subscribed to the lists selected for this message.
        $this->loadLists();
        if (!empty($this->lists)) {
            $query['where'] = ['message_list_id' => ['IN', $this->lists]];
        }

        // Make sure the message is never resent.
        if ($this->auto || !empty($this->never_resend)) {
            $query['join'][] = [
                'left_join' => 'tracker_event',
                'on' => [
                    'tracker_event.user_id' => ['user.user_id'],
                    'tracker_event.tracker_id' => self::$message_sent_id,
                    'tracker_event.sub_id' => $this->message_id,
                ]
            ];
            $query['where']['tracker_event.user_id'] = null;
        }

        // Make sure the user matches a criteria.
        $this->loadCriteria();
        foreach ($this->criteria as $criteria) {
            $field_values = json_decode($criteria['field_values'], true);

            $criteria_filter = json_decode($criteria['filter'], true);
            $this->replaceCriteriaVariables($criteria_filter, $field_values);

            // Merge into the current search query.
            Database::filterQuery($query, $criteria_filter);
        }

        if (empty($this->lists) && empty($this->criteria)) {
            throw new \Exception('Your message does not have any mailing lists or criteria specified.');
        }

        if (!empty($this->limit)) {
            $query['limit'] = $this->limit;
        }
        if (!empty($this->random)) {
            $query['order_by'] = [['expression' => 'RAND()']];
        }

        return $query;
    }

    /**
     * Performs a string replace
     *
     * @param string $query
     * @param array $variables
     * @return mixed
     */
    protected function replaceCriteriaVariables(&$query, $variables = []) {
        if (empty($variables['TODAY'])) {
            $variables['TODAY'] = Time::today();
        }
        if (empty($variables['NOW'])) {
            $variables['NOW'] = time();
        }

        foreach ($variables as $key => $value) {
            array_walk_recursive($query, function(&$item, $key) use ($variables) {
                if ($item !== null) {
                    foreach ($variables as $var => $value) {
                        $item = str_replace('{' . $var . '}', $value, $item);
                    }
                    $matches = [];
                    if (preg_match('/{TRACKER:(.*):(.*)}/', $item, $matches)) {
                        $tracker = Tracker::loadOrCreateByName($matches[2], $matches[1]);
                        $item = str_replace('{TRACKER:' . $tracker->category . ':' . $tracker->tracker_name . '}', $tracker->id, $item);
                    }
                }
            });
        }
    }

    /**
     * Gets a list of users from the database.
     *
     * @return \PDOStatement
     *   An object to iterate all the users who will receive the email.
     *
     * @throws \Exception
     */
    public function getUsers() {
        $query = $this->getUsersQuery();
        return Database::getInstance()->selectQuery($query);
    }

    /**
     * Get a count of how many users will receive this message.
     *
     * @return integer
     *   The number of users.
     *
     * @throws \Exception
     */
    public function getUsersCount() {
        $query = $this->getUsersQuery();
        if (!empty($query['group_by'])) {
            // Count as a subquery if there are group by clauses.
            $query['select']['user_id'] = 'user.user_id';
            $query = [
                'select' => ['count' => ['expression' => 'COUNT(DISTINCT(user_id))']],
                'from' => ['subtable' => $query]
            ];
        } else {
            // Otherwise just count distinct users.
            $query['select']['count'] = ['expression' => 'COUNT(DISTINCT(user.user_id))'];
        }
        return Database::getInstance()->countQuery($query);
    }

    /**
     * Sets an email template
     *
     * @param integer $template_id
     *
     * @throws \Exception
     */
    public function setTemplate($template_id) {
        $this->template = Database::getInstance()->selectRow(
            'message_template',
            ['template_id' => $template_id]
        );
    }

    /**
     * Gets the stats for a message.
     *
     * @param int $user_id
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getStats($user_id = null) {
        if (empty($this->id)) {
            throw new \Exception("Can't get message stats without a message id.");
        }

        return Database::getInstance()->query('SELECT COUNT(*) AS count, MIN(time) AS first, MAX(time) AS last FROM tracker_event WHERE tracker_id = ? AND user_id = ? AND sub_id = ?', [
            Tracker::loadOrCreateByName('Email Sent', Tracker::EMAIL)->id,
            $user_id ?? 0,
            $this->id,
        ])->fetch();
    }
}
