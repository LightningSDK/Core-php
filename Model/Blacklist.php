<?php

namespace lightningsdk\core\Model;

use Exception;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Scrub;

class Blacklist extends BaseObject {
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
