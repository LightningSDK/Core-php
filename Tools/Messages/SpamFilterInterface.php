<?php

namespace lightningsdk\core\Tools\Messages;

interface SpamFilterInterface {
    /**
     * Pass in a message to get a score.
     *
     * @param array $clientFields
     * @param array $messageFields
     * @param array $spamFields
     *
     * @return mixed
     */
    public static function getScore(&$clientFields, &$messageFields, &$spamFields);

    /**
     * Mark this message as spam.
     *
     * @param array $clientFields
     * @param array $messageFields
     * @param array $spamFields
     */
    public static function flagAsSpam(&$clientFields, &$messageFields, &$spamFields);
}
