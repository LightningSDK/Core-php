<?php

namespace Lightning\Tools\Messages;

class BlackList implements SpamFilterInterface {

    /**
     * @param array $message
     *
     * @return int
     *   5 if it was found in the blacklist or 0 if not
     */
    public static function getScore(&$clientFIelds, &$messageFields, &$spamFields) {
        if (!empty($clientFIelds['IP'])) {
            return \Lightning\Model\Blacklist::checkBlacklist($clientFIelds['IP']) ? 5 : 0;
        }

        return 0;
    }

    /**
     * @param $message
     * @throws \Exception
     */
    public static function flagAsSpam(&$clientFIelds, &$messageFields, &$spamFields) {
        \Lightning\Model\Blacklist::addToBlacklist($clientFIelds['IP']);
    }

}
