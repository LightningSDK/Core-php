<?php

namespace Lightning\View;

class NumberFormat {

    protected static $abbreviatedSuffix = ['', 'K', 'M', 'B', 'T', 'Q'];
    protected static $abbreviatedDataSuffix = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    public static function abbreviatedNumber($number) {
        return self::abbreviated($number, 1000, self::$abbreviatedSuffix);
    }

    protected static function abbreviated($number, $division, $abbreviations) {
        $output = $number;
        $suffix = 0;
        while ($output >= $division) {
            $output /= $division;
            $suffix++;
        }

        return number_format($output, 1) . $abbreviations[$suffix];
    }
}
