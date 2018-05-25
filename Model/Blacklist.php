<?php

namespace Lightning\Model;

use Exception;
use Lightning\Tools\Database;
use Lightning\Tools\Scrub;

class Blacklist extends Object {
    const TABLE = 'black_list';
    const PRIMARY_KEY = 'black_list_id';

    /**
     * @param $ip
     * @return bool
     */
    public static function checkBlacklist($ip) {
        if ($ip = Scrub::ipToHex($ip)) {
            if (Database::getInstance()->check(self::TABLE, [
                'ip_start' => ['<=', $ip],
                'ip_end' => ['>=', $ip],
            ])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $ip
     * @throws Exception
     */
    public static function addToBlacklist($ip) {
        if ($ip = Scrub::ipToHex($ip)) {
            Database::getInstance()->insert(self::TABLE, [
                'ip_start' => $ip,
                'ip_end' => $ip,
            ]);
        } else {
            throw new Exception('Invalid IP Address.');
        }
    }
}
