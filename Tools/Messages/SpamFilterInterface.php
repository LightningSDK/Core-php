<?php

namespace Lightning\Tools\Messages;

interface SpamFilterInterface {
    /**
     * Pass in a message to get a score.
     *
     * @param $message
     * @return mixed
     */
    public static function getScore(&$message);

    /**
     * Mark this message as spam.
     *
     * @param $message
     */
    public static function flagAsSpam($message);
}
