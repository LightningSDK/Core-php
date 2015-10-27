<?php

namespace Lightning\Tools;

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
        if (!empty($error)) {
            self::$errors[] = $error;
        }
        if (self::$verbose) {
            echo "Error: $error \n";
        }
    }

    /**
     * Add a message to the queue.
     *
     * @param string $message
     *   The new message.
     */
    public static function message($message) {
        if (!empty($message)) {
            self::$messages[] = $message;
        }
        if (self::$verbose) {
            echo "Message: $message \n";
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
            $lang = Language::getInstance();
            $messages = explode(',', $messages);
            foreach ($messages as $message) {
                self::message($lang->translate($message));
            }
        }
        $errors = Request::query('err');
        if (!empty($errors)) {
            $lang = Language::getInstance();
            $errors = explode(',', $errors);
            foreach ($errors as $error) {
                self::error($lang->translate($error));
            }
        }
    }

    /**
     * Save the current messages and errors to the session.
     */
    public static function storeInSession() {
        // If there is nothing to save, return to prevent session creation.
        if (empty(self::$messages) && empty(self::$messages)) {
            return;
        }

        $session = Session::getInstance();
        if (!empty(self::$messages)) {
            $session->setSettings('messages.messages', self::$messages);
        }
        if (!empty(self::$errors)) {
            $session->setSettings('messages.errors', self::$errors);
        }
        $session->saveData();
    }

    /**
     * Load messages and errors from the session.
     */
    public static function loadFromSession() {
        if ($session = Session::getInstance(false)) {
            self::$messages = array_merge(self::$messages, $session->getSetting('messages.messages', array()));
            self::$errors = array_merge(self::$errors, $session->getSetting('messages.errors', array()));
            $session->unsetSetting('messages');
            $session->saveData();
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
}
