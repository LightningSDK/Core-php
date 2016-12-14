<?php

namespace Lightning\Tools;

class Performance {

    protected static $startTime;

    public static function startTimer() {
        if (empty(self::$startTime)) {
            self::$startTime = microtime(true);
        }
    }

    public static function timeReport() {
        $output = [];
        $db = Database::getInstance(false);
        if ($db) {
            $output += $db->timeReport();
        }

        // TODO: Add times for HTTP requests.

        // TODO: Add times for MongoDB.

        $output['Total PHP Time'] = self::getRunningTime();

        return $output;
    }

    public static function getRunningTime() {
        return number_format(microtime(true) - self::$startTime, 4);
    }
}
