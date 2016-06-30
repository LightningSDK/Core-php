<?php

namespace Lightning\Tools;
use stdClass;

/**
 * Class Messenger
 * @package Lightning\Tools
 *
 * A static class for handling errors and messages.
 */
class Messenger {
    /**
     * A list of messages to output.
     *
     * @var array
     */
    protected static $messages = array();

    /**
     * A list of errors to output.
     *
     * @var array
     */
    protected static $errors = array();

    /**
     * Whether messages should be output immediately, ie for CLI.
     *
     * @var boolean
     */
    protected static $verbose = false;

    /**
     * Add an error to the queue.
     *
     * @param string $error
     *   The new error.
     */
    public static function error($error) {
        if (self::$verbose) {
            echo "Error: $error \n";
        }
        else if (!empty($error)) {
            self::$errors[] = $error;
        }
    }

    /**
     * Add a message to the queue.
     *
     * @param string $message
     *   The new message.
     */
    public static function message($message) {
        if (self::$verbose) {
            echo "Message: $message \n";
        }
        else if (!empty($message)) {
            self::$messages[] = $message;
        }
    }

    /**
     * Whether there are errors set.
     *
     * @return boolean
     */
    public static function hasErrors() {
        return !empty(self::$errors);
    }

    /**
     * Whether there are messages set.
     *
     * @return boolean
     */
    public static function hasMessages() {
        return !empty(self::$messages);
    }

    /**
     * Get a list of errors.
     *
     * @return array
     *   A list of errors.
     */
    public static function getErrors() {
        return self::$errors;
    }

    /**
     * Get a list of messages.
     *
     * @return array
     *   A list of messages.
     */
    public static function getMessages() {
        return self::$messages;
    }

    /**
     * Load messages and errors from the query string.
     */
    public static function loadFromQuery() {
        $messages = Request::query('msg');
        if (!empty($messages)) {
            $messages = explode(',', $messages);
            foreach ($messages as $message) {
                self::message(Language::translate($message));
            }
        }
        $errors = Request::query('err');
        if (!empty($errors)) {
            $errors = explode(',', $errors);
            foreach ($errors as $error) {
                self::error(Language::translate($error));
            }
        }
    }

    /**
     * Save the current messages and errors to the session.
     */
    public static function storeInSession() {
        // If there is nothing to save, return to prevent session creation.
        if (empty(self::$messages) && empty(self::$errors)) {
            return;
        }

        $session = Session::getInstance();
        if (!empty(self::$messages)) {
            if (empty($session->content->messages)) {
                $session->content->messages = new stdClass();
            }
            $session->content->messages->messages = self::$messages;
        }
        if (!empty(self::$errors)) {
            if (empty($session->content->messages)) {
                $session->content->messages = new stdClass();
            }
            $session->content->messages->errors = self::$errors;
        }
        $session->save();
    }

    /**
     * Load messages and errors from the session.
     */
    public static function loadFromSession() {
        if ($session = Session::getInstance(true, false)) {

            $session_messages = !empty($session->content->messages->messages) ? $session->content->messages->messages : [];
            $session_errors = !empty($session->content->messages->errors) ? $session->content->messages->errors : [];
            $reset = false;
            if (!empty($session_messages)) {
                self::$messages = array_merge(self::$messages, $session_messages);
                $reset = true;
            }
            if (!empty($session_errors)) {
                self::$errors = array_merge(self::$errors, $session_errors);
                $reset = true;
            }
            if ($reset) {
                unset($session->content->messages);
                $session->save();
            }
        }
    }

    /**
     * Set the verbose variable.
     *
     * @param boolean $verbose
     *   Whether the verbose mode should be on.
     */
    public static function setVerbose($verbose = true) {
        self::$verbose = $verbose;
    }

    public static function renderErrorsAndMessages() {
        $output = '';
        if (!empty(self::$errors)) {
            $output .= '<div class="messenger error">';
            foreach (self::$errors as $error) {
                $output .= '<li>' . $error . '</li>';
            }
            $output .= '</div>';
        }
        if (!empty(self::$messages)) {
            $output .= '<div class="messenger message">';
            foreach (self::$messages as $message) {
                $output .= '<li>' . $message . '</li>';
            }
            $output .= '</div>';
        }

        return $output;
    }
}
