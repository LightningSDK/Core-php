<?php

namespace Lightning\Tools\Messages;

interface SpamFilterInterface {
    /**
     * Pass in a message to get a score.
     *
     * @param array $clientFIelds
     * @param array $messageFields
     * @param array $spamFields
     *
     * @return mixed
     */
    public static function getScore(&$clientFIelds, &$messageFields, &$spamFields);

    /**
     * Mark this message as spam.
     *
     * @param array $clientFIelds
     * @param array $messageFields
     * @param array $spamFields
     */
    public static function flagAsSpam(&$clientFIelds, &$messageFields, &$spamFields);
}
