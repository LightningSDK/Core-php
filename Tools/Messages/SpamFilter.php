<?php

namespace Lightning\Tools\Messages;

use Lightning\Tools\Configuration;

class SpamFilter {

    /**
     * This should score a message from 0 meaning not spam to any positive number.
     * Each class that implements the SpamFilterInterface can add it's own score
     * The system should be configured with a spam threshold as to whether the message is spam.
     *
     * @param array $clientFIelds
     * @param array $messageFields
     * @param array $spamFields
     *
     * @return float
     */
    public static function getScore(&$clientFIelds, &$messageFields, &$spamFields) {
        $handlers = Configuration::get('messages.spamFilters');
        $score = 0;
        foreach ($handlers as $handler) {
            $score += $handler::getScore($clientFIelds, $messageFields, $spamFields);
        }
        return $score;
    }

    public static function flagAsSpam(&$clientFIelds, &$messageFields, &$spamFields) {
        $handlers = Configuration::get('messages.spamFilters');
        foreach ($handlers as $handler) {
            $handler::flagAsSpam($clientFIelds, $messageFields, $spamFields);
        }
    }
}
