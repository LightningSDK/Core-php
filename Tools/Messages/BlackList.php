<?php

namespace lightningsdk\core\Tools\Messages;

class BlackList implements SpamFilterInterface {

    /**
     * @param array $message
     *
     * @return int
     *   5 if it was found in the blacklist or 0 if not
     */
    public static function getScore(&$clientFields, &$messageFields, &$spamFields) {
        if (!empty($clientFields['IP'])) {
            return \lightningsdk\core\Model\Blacklist::checkBlacklist($clientFields['IP']) ? 5 : 0;
        }

        return 0;
    }

    /**
     * @param $message
     * @throws \Exception
     */
    public static function flagAsSpam(&$clientFields, &$messageFields, &$spamFields) {
        \lightningsdk\core\Model\Blacklist::addToBlacklist($clientFields['IP']);
    }

}
