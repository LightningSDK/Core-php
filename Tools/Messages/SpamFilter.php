<?php

namespace Lightning\Tools\Messages;

use Lightning\Tools\Configuration;

class SpamFilter {

    /**
     * This should score a message from 0 meaning not spam to any positive number.
     * Each class that implements the SpamFilterInterface can add it's own score
     * The system should be configured with a spam threshold as to whether the message is spam.
     *
     * @param array $message
     *
     * @return float
     */
    public static function getScore($message) {
        $handlers = Configuration::get('messages.spamFilters');
        $score = 0;
        foreach ($handlers as $handler) {
            $score += $handler::getScore($message);
        }
        return $score;
    }

    public static function flagAsSpam($message) {
        $handlers = Configuration::get('messages.spamFilters');
        foreach ($handlers as $handler) {
            $handler::flagAsSpam($message);
        }
    }
}
