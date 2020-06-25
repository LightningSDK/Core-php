<?php

namespace lightningsdk\core\Tools\Messages;

use lightningsdk\core\Tools\Configuration;

class SpamFilter {

    /**
     * This should score a message from 0 meaning not spam to any positive number.
     * Each class that implements the SpamFilterInterface can add it's own score
     * The system should be configured with a spam threshold as to whether the message is spam.
     *
     * @param array $clientFields
     * @param array $messageFields
     * @param array $spamFields
     *
     * @return float
     */
    public static function getScore(&$clientFields, &$messageFields, &$spamFields) {
        $handlers = Configuration::get('messages.spamFilters');
        $score = 0;
        foreach ($handlers as $handler) {
            $score += $handler::getScore($clientFields, $messageFields, $spamFields);
        }
        return $score;
    }

    public static function flagAsSpam(&$clientFields, &$messageFields, &$spamFields) {
        $handlers = Configuration::get('messages.spamFilters');
        foreach ($handlers as $handler) {
            $handler::flagAsSpam($clientFields, $messageFields, $spamFields);
        }
    }
}
