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
     * Add an error to the queue.
     *
     * @param string $error
     *   The new error.
     */
    public static function error($error) {
        if (!empty($error)) {
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
        if (!empty($message)) {
            self::$messages[] = $message;
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
        $messages = explode(',', Request::query('msg'));
        $errors = explode(',', Request::query('err'));
        $lang = Language::getInstance();
        foreach ($messages as $message) {
            self::message($lang->translate($message));
        }
        foreach ($errors as $error) {
            self::error($lang->translate($error));
        }
    }
}
