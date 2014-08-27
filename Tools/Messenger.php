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
        $errors = Request::query('err');
        if (!empty($messages)) {
            $lang = Language::getInstance();
            $messages = explode(',', $messages);
            foreach ($messages as $message) {
                self::message($lang->translate($message));
            }
        }
        if (!empty($errors)) {
            $lang = Language::getInstance();
            $errors = explode(',', $errors);
            foreach ($errors as $error) {
                self::error($lang->translate($error));
            }
        }
    }

    public static function setVerbose($verbose = true) {
        self::$verbose = $verbose;
    }
}
