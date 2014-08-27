<?php

namespace Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;

class Message {

    protected $formatted_message = array();
    protected $message;
    protected $template;
    protected $test;
    protected $unsubscribe = true;
    protected $custom_variables = array();

    public function __construct($message_id, $unsubscribe = true) {
        $this->message = Database::getInstance()->selectRow('message', array('message_id' => $message_id));
        $this->loadTemplate();
        $this->loadCriteria();
        $this->unsubscribe = $unsubscribe;
    }

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

    protected function loadCriteria() {

    }

    public function setTest() {
        $this->test = true;
    }

    public function setUser($user) {
        $this->user = $user;
    }

    protected function getUnsubscribeString() {
        return 'You&rsquo;re receiving this as you previously enquired with us about a product or business or you&rsquo;re a friend through social media. To stop receiving these emails, '
            ."<a href='http://" . Configuration::get('site.domain') . "/user.php?action=unsubscribe&email=" . urlencode($this->user['email']) . "&code=".User::unsubscribeKey($this->user['user_id'], $this->user['email'])
            ."'>click here</a>";

    }

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

    public function getSubject() {
        // Start by combining subject and template.
        $subject = !empty($this->message['subject']) ? $this->message['subject'] : $this->template['subject'];
        $subject = $this->replaceVariables($subject);

        return ($this->test ? 'TEST ' : '') . $subject;
    }

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

    protected function getUsersQuery() {
        $table = array(
            'from' => 'user',
        );
        $where = array(
            'active' => 1,
        );

        return array('table' => $table, 'where' => $where);
    }

    public function getUsers() {
        $query = $this->getUsersQuery();
        return Database::getInstance()->select($query['table'], $query['where']);
    }

    public function getUsersCount() {
        $query = $this->getUsersQuery();
        return Database::getInstance()->count($query['table'], $query['where']);
    }
}
