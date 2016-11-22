<?php

namespace Lightning\Model;

use Exception;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;

class Blacklist extends Object {
    const TABLE = 'black_list';
    const PRIMARY_KEY = 'black_list_id';

    public static function checkBlacklist() {
        $ip = Request::getIP();
        if ($ip = Scrub::ipToHex($ip)) {
            if (!Database::getInstance()->check(self::TABLE, [
                'ip_start' => ['<=', $ip],
                'ip_end' => ['>=', $ip],
            ])) {
                return true;
            }
        }

        throw new Exception('This action has been denied for security purposes.');
    }
}
