<?php
/**
 * @file
 * Contains Lightning\Pages\Page
 */

namespace Lightning\Pages;

use Exception;
use Lightning\Model\URL;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Language;
use Lightning\Tools\Mailer;
use Lightning\Tools\Messages\SpamFilter;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\ReCaptcha;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Template;
use Lightning\View\Page as PageView;
use Lightning\Model\User as UserModel;
use Lightning\Model\Message;
use Lightning\Model\Tracker;
use Lightning\Model\Contact as ContactModel;

/**
 * A contact page handler.
 *
 * @package Lightning\Pages
 *
 * To use this page handler, create a form that posts to the url attached.
 * Fields that can be used include:
 *   list: Subscribe the user to the list with this message_list_id.
 *   optin: Subscribe the user to a default list.
 *   contact: Boolean, whether to notify the site admins. This will send anyway if contact.always_notify is set to true in the configuration.
 *   message: If set, a message with this message_id will be sent to the input user email.
 *   redirect: The success page.
 *
 */
class Contact extends PageView {

    protected $page = ['contact', 'Lightning'];
    protected $menuContext = 'contact';
    protected $share = false;

    /**
     * @var ContactModel
     */
    protected $contact;

    /**
     * An ID of a list if the user is being subscribed.
     *
     * @var integer
     */
    protected $list;

    /**
     * The User object for the user who submitted the form.
     *
     * @var UserModel
     */
    protected $user;

    /**
     * Additional values to be stored in message.
     *
     * @var array
     */
    protected $additionalFields;

    /**
     * Whether the site contacts should be notified.
     *
     * @var boolean
     */
    protected $requestContact = false;

    /**
     * A list of settings for the contact form.
     *
     * @var array
     */
    protected $settings;

    /**
     * If set, a message with this ID will be sent to the user who submitted the contact form.
     *
     * @var integer
     */
    protected $userMessage = 0;

    /**
     * Set to 1 if the user message was successfully sent.
     *
     * @var integer
     */
    protected $userMessageSent = 0;

    /**
     * Set to 1 if the site contact will be sent a message.
     *
     * @var integer
     */
    protected $contactAdmin = 0;

    /**
     * Set to 1 if the site contact was successfully sent.
     *
     * @var integer
     */
    protected $contactAdminSent = 0;

    /**
     * TODO: Previously this did not require a token because it was not a security concern and
     * we didn't want to create a session for a contact form on every page. However now that
     * the form tokens are based on encrypted cookies, we can require tokens.
     *
     * @var boolean
     */
    protected $ignoreToken = false;

    /**
     * The spam score of the message submitted.
     *
     * @var integer
     */
    protected $spamScore;

    /**
     * Gets spam fields and score.
     *
     * @var array
     */
    protected $spamFields;

    protected function hasAccess() {
        return true;
    }

    public function get() {

    }

    /**
     * Send a posted contact request to the site admin.
     *
     * @throws Exception
     */
    public function post() {
        // Initialize and validate
        $this->loadVars();
        try {
            $this->validateForm();
        } catch (Exception $e) {
            Messenger::error($e->getMessage());
            return $this->get();
        }

        // Create a contact entry
        $this->contact = new ContactModel($this->getContactFields());
        $this->contact->save();

        // If this is not spam, subscribe/message the user.
        if (!$this->isSpam()) {
            // Subscribe the user
            $this->optinUser();

            // Send messages to the user and site contacts
            $this->messageUser();
        }

        $this->messageSiteContact();

        // Update the contact information
        $this->contact->setData($this->getContactFields());

        // Redirect the user
        $this->redirect();
    }

    /**
     * Load some variables from the form submission.
     */
    protected function loadVars() {
        $this->settings = Configuration::get('contact');
        $this->requestContact = Request::post('contact', Request::TYPE_BOOLEAN);
        $this->userMessage = Request::post('message', Request::TYPE_INT, '', 0);
        if ($this->list = Request::get('list', Request::TYPE_INT, '', 0)) {
            if (!Message::validateListID($this->list)) {
                $this->list = Message::getDefaultListID();
            }
        }
        if (empty($this->list) && Request::get('optin', Request::TYPE_BOOLEAN)) {
            $this->list = Message::getDefaultListID();
        }
    }

    /**
     * Run some checks on the form to make sure the required fields are submitted.
     *
     * @throws Exception
     */
    protected function validateForm() {
        // Check captcha if required.
        if (
            !empty($this->settings['require_captcha'])
            && (
                $this->settings['require_captcha'] === true
                || (
                    $this->settings['require_captcha'] == 'contact_only'
                    && $this->requestContact
                )
            )
            && !ReCaptcha::verify()
        ) {
            throw new Exception('You did not correctly enter the captcha code.');
        }

        // Make sure the sender's email address is valid.
        if (!$this->getSender()) {
            throw new Exception('Please enter a valid email address.');
        }
    }

    /**
     * If the settings direct it, this will subscribe the user to a mailing list.
     */
    protected function optinUser() {
        if (!empty($this->list)) {
            $this->user->subscribe($this->list);
        }
    }

    /**
     * If the settings direct it, this will send a message to the user who filled in the contact form.
     *
     * @throws Exception
     */
    protected function messageUser() {
        // Send a message to the user who just opted in, as long as this is not spam.
        if ($this->userMessage) {
            $mailer = new Mailer();
            $this->userMessageSent = $mailer->sendOne($this->userMessage, $this->user);
        }
    }

    /**
     * If the settings direct it, this will send a contact notification to the site contacts.
     *
     * @throws Exception
     */
    protected function messageSiteContact() {
        // Send a message to the site contact.
        if (!empty($this->settings['always_notify']) || ($this->requestContact && $this->settings['contact'])) {
            $sent = $this->sendMessage();
            if (!$sent) {
                Output::error('Your message could not be sent. Please try again later.');
            } else {
                // Count the email ass sent.
                Tracker::loadOrCreateByName('Contact Sent', Tracker::EMAIL)->track(URL::getCurrentUrlId(), $this->user->id);

                // Send an email to have them test for spam.
                // TODO: This should be moved to the message sent to the user.
                if (!empty($this->settings['auto_responder'])) {
                    $auto_responder_mailer = new Mailer();
                    $result = $auto_responder_mailer->sendOne($this->settings['auto_responder'], $this->user);
                    if ($result && $this->settings['spam_test']) {
                        // Set the notice.
                        $this->setSuccessMessage(Language::translate('spam_test'));
                        return;
                    }
                }
                $this->setSuccessMessage(Language::translate('contact_sent'));
                return;
            }
        } else {
            $this->setSuccessMessage(Language::translate('optin.success'));
            return;
        }
    }

    /**
     * Get the fields for the contact table.
     *
     * @return array
     */
    public function getContactFields() {
        $this->processAdditionalFields();
        return [
            'user_id' => $this->user->id,
            'time' => time(),
            'contact' => $this->contactAdmin,
            'contact_sent' => $this->contactAdminSent,
            'list_id' => $this->list,
            'user_message' => $this->userMessage,
            'user_message_sent' => $this->userMessageSent,
            'additional_fields' => $this->additionalFields + $this->getSpamFields(),
        ];
    }

    /**
     * Redirect to the next page. If a redirect field is set in the form, it will go there. If not, it will redirect
     * to the /message page.
     *
     * @param array $params
     *   Additional query parameters.
     */
    public function redirect($params = []) {
        if ($redirect = Request::post('redirect')) {
            Navigation::redirect($redirect, $params);
        } else {
            Navigation::redirect('/message', $params);
        }
    }

    /**
     * Add a custom message from the form input.
     *
     * @param string $default
     *   The default success message.
     */
    protected function setSuccessMessage($default) {
        if ($this->settings['custom_message'] && $message = Request::post('success')) {
            Messenger::message($message);
        } elseif (isset($_POST['success'])) {
            return;
        } else {
            Messenger::message($default);
        }
    }

    /**
     * Load the user data and save it into a user entry.
     *
     * @return bool
     * @throws \Exception
     */
    protected function getSender() {
        if ($data = Request::post('name', '', '', '')) {
            $name_parts = explode(' ', $data, 2);
            $data = ['first' => $name_parts[0]];
            if (!empty($name_parts[1])) {
                $data['last'] = $name_parts[1];
            }
        } else {
            $data = [
                'first' => Request::post('first', '', '', ''),
                'last' => Request::post('last', '', '', ''),
            ];
        }

        if ($ref = ClientUser::getReferrer()) {
            // Set the referrer.
            $data['referrer'] = $ref;
        }

        // Add the user to the database.
        $email = Request::post('email', Request::TYPE_EMAIL);
        if (empty($email)) {
            return false;
        }
        $this->user = UserModel::addUser($email, $data);
        return true;
    }

    /**
     * Send a message to the site contact.
     *
     * @return boolean
     *   Whether the email was successfully sent.
     */
    public function sendMessage() {
        $this->contactAdmin = 1;
        $mailer = new Mailer();
        foreach ($this->settings['to'] as $to) {
            $mailer->to($to);
        }

        // Set the reply-to as the user who is sending the contact for easy reply.
        if (!isset($this->settings['reply_to_sender']) || $this->settings['reply_to_sender'] !== false) {
            $mailer->replyTo($this->user->email);
        }

        $subject = $this->settings['subject'];
        $message = $this->getMessageBody();

        if ($this->isSpam()) {
            $subject = 'SPAM: ' . $subject;
            $errors = [];
            if (!empty($this->list)) {
                $errors[] = 'the user was not subscribed to list ' . $this->list;
            }
            if (!empty($this->userMessage)) {
                $errors[] = 'the user was not sent message ' . $this->userMessage;
            }
            if (!empty($errors)) {
                $message .= '<br><br> This message was flagged as spam. As a result ' . implode(', ', $errors) . '.';
            }
        } else {
            // Append "flag as spam" link
            $encryptedMessageId = Encryption::aesEncrypt($this->contact->id, Configuration::get('user.key'));
            $message .= '<br><br><br>';
            $message .= '<a href="' . Configuration::get('web_root') . '/contact?action=spam&message=' . $encryptedMessageId . '">Mark this message as spam</a>';
        }

        return $this->contactAdminSent = (integer) $mailer
            ->subject($subject)
            ->message($message)
            ->send();
    }

    /**
     * Get a list of fields submitted to the form, excluding control fields.
     */
    protected function processAdditionalFields() {
        if ($this->additionalFields === null) {
            $fields = array_combine(array_keys($_POST), array_keys($_POST));
            $this->additionalFields = [
                'Name' => Request::post('name'),
                'Email' => $this->user->email,
                'IP' => Request::server(Request::IP),
                'URL' => $this->getReferer(),
            ];

            unset($fields['token']);
            unset($fields['name']);
            unset($fields['email']);
            unset($fields['contact']);
            unset($fields['success']);
            unset($fields['list']);
            unset($fields['g-recaptcha-response']);
            unset($fields['captcha_abide']);
            unset($fields['url']);

            foreach ($fields as $field) {
                if (is_array($_POST[$field])) {
                    $input = json_encode(Request::post($field, 'array'));
                } else {
                    $input = Request::post($field);
                }
                $this->additionalFields[ucfirst(preg_replace('/_/', ' ', $field))] = $input;
            }
        }
    }

    protected function getSpamFields() {
        if ($this->spamFields === null) {
            $this->processAdditionalFields();
            $this->spamFields = [
                'Spam' => $this->isSpam() ? 'Yes' : 'No',
                'Spam Score' => $this->getSpamScore(),
            ];
        }
        return $this->spamFields;
    }

    /**
     * Get the submission URL. If not explicitly supplied in the form, it will try to get it from the HTTP header.
     *
     * @return string
     */
    protected function getReferer() {
        if ($url = Request::post('URL')) {
            return $url;
        } else {
            return Request::getReferrer();
        }
    }

    /**
     * Create the message body to the site contact.
     *
     * @return string
     */
    protected function getMessageBody() {
        $this->processAdditionalFields();
        $fields = $this->additionalFields + $this->getSpamFields();

        $output = '';
        foreach ($fields as $key => $value) {
            if ($key != 'Message') {
                $output .= $key . ': ' . $value . "<br>\n";
            }
        }
        if (!empty($fields['Message'])) {
            $output .= "Message: <br>\n" . $fields['Message'];
        }

        return $output;
    }

    protected function getSpamScore() {
        if ($this->spamScore === null) {
            $this->processAdditionalFields();
            $this->spamScore = SpamFilter::getScore($this->additionalFields);
        }
        return $this->spamScore;
    }

    /**
     * Determine whether this message is spam.
     *
     * @return boolean
     */
    protected function isSpam() {
        $this->getSpamScore();

        return $this->spamScore > Configuration::get('messages.maxAllowableScore');
    }

    /**
     * Flag a message as spam
     *
     * Query parameters:
     *   message: encrypted message ID
     *
     * @throws Exception
     */
    public function getSpam() {
        $input = Request::get('message', Request::TYPE_ENCRYPTED);
        $id = Encryption::aesDecrypt($input, Configuration::get('user.key'));
        $message = ContactModel::loadByID($id);
        if (empty($message)) {
            throw new Exception('Invalid message');
        }

        $template = Template::getInstance();
        $template->set('values', $input);
        $template->set('confirmationMessage', 'Are you sure you want to mark this message as spam?');
        $template->set('successAction', 'spam');
        $this->page = ['confirmation', 'Lightning'];
    }

    /**
     * Confirm that this message is to be marked as spam
     *
     * @throws Exception
     */
    public function postSpam() {
        $input = Request::post('values', Request::TYPE_ENCRYPTED);
        $id = Encryption::aesDecrypt($input, Configuration::get('user.key'));
        $message = ContactModel::loadByID($id);
        if (empty($message)) {
            throw new Exception('Invalid message');
        }
        $message->spam = 1;
        $message->save();
        SpamFilter::flagAsSpam(Scrub::objectToAssocArray($message->additional_fields));
        Messenger::message('This message has been flagged as spam.');
        Navigation::redirect('/message');
    }
}
