<?php

namespace Lightning\Tools\Messages;

class BlackList implements SpamFilterInterface {

    /**
     * @param array $message
     *
     * @return int
     *   5 if it was found in the blacklist or 0 if not
     */
    public static function getScore($message) {
        if (!empty($message['IP'])) {
            return \Lightning\Model\Blacklist::checkBlacklist($message['IP']) ? 5 : 0;
        }

        return 0;
    }

    public static function flagAsSpam($message) {
        \Lightning\Model\Blacklist::addToBlacklist($message['IP']) ? 5 : 0;
    }

}
