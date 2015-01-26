<?php

namespace Lightning\View\Field;

use DateTime;
use DateTimeZone;
use Lightning\Tools\Request;
use Lightning\View\Field;

class Time extends Field {
    /**
     * Get today's date on the JD calendar.
     *
     * @return integer
     *   The JD date of the server.
     */
    public static function today() {
        return gregoriantojd(date('m'), date('d'), date('Y'));
    }

    /**
     * Create a string like 2 hours, 4 minutes, and 21 seconds.
     *
     * @param $time
     *   The time in seconds.
     *
     * @return string
     *   The formatted time.
     */
    public static function formatLength($time) {
        $seconds = $time % 60;
        $minutes = floor($time / 60) % 60;
        $hours = floor($time / 3600);

        if ($hours > 0) {
            return "$hours hours, $minutes minutes, and $seconds seconds";
        } elseif ($minutes > 0) {
            return "$minutes minutes and $seconds seconds";
        } else {
            return "$seconds seconds";
        }
    }

    public static function getDate($id, $allow_blank = true) {
        $m = Request::get($id ."_m");
        $d = Request::get($id ."_d");
        $y = Request::get($id ."_y");
        if($m > 0 && $d > 0){
            if($y == 0) $y = date("Y");
            return gregoriantojd($m, $d, $y);
        } elseif (!$allow_blank) {
            return gregoriantojd(date("m"),date("d"),date("Y"));
        } else {
            return 0;
        }
    }

    public static function getTime($id, $allow_blank = true) {
        $h = Request::get($id .'_h', 'int');
        $i = Request::get($id .'_i');
        $a = Request::get($id .'_a');
        if (empty($h)) {
            if ($allow_blank) {
                return 0;
            } else {
                $time = explode("/",date("h/i/a",time()));
                $h = $time[0];
                $i = $time[1];
                $a = $time[2];
            }
        }
        if ($a == "PM") {
            $h += 12;
        }
        return ($h * 60) + $i;
    }

    public static function getDateTime($id, $allow_blank = true) {
        $m = Request::get($id .'_m', 'int');
        $d = Request::get($id .'_d', 'int');
        $y = Request::get($id .'_y', 'int');
        $h = Request::get($id .'_h', 'int');
        $i = str_pad(Request::get($id .'_i', 'int'), 2, 0, STR_PAD_LEFT);
        $h += Request::get($id . '_a', '', '', 'AM') == 'AM' ? 0 : 12;

        if ($allow_blank && (empty($m) || empty($d) || empty($y) || empty($h))) {
            return 0;
        }

        return gmmktime($h, $i, 0, $m, $d, $y);
    }

    public static function printDate($value) {
        if($value == 0) return '';
        $date = explode('/',jdtogregorian($value));
        return "{$date[0]}/{$date[1]}/{$date[2]}";
    }

    public static function printTime($value) {
        if ($value == 0) {
            return '';
        }
        $i = str_pad($value % 60, 2, 0, STR_PAD_LEFT);
        $h = ($value - $i) / 60;
        if ($h > 12) {
            $a = "PM";
            $h -= 12;
        } else {
            $a = "AM";
        }
        return "{$h}:{$i} {$a}";
    }

    public static function printDateTime($value) {
        if(empty($value)) {
            return '';
        } else {
            $date = new Datetime('@' . $value, new DateTimeZone('UTC'));
            return $date->format('m/d/Y h:ia');
        }
    }

    public static function datePop($field, $value, $allow_zero, $first_year = 0){
        if(!$allow_zero && ($value == 0 || $value == '')){
            $date = array(date('m'), date('d'), date('Y'));
        } else $date = explode('/', jdtogregorian($value));
        $output = self::monthPop($field . '_m', $date[0], $allow_zero);
        $output .= ' / ';
        $output .= self::dayPop($field . '_d', $date[1], $allow_zero);
        $output .= ' / ';
        $output .= self::yearPop($field . '_y', $date[2], $allow_zero, $first_year);
        return $output;
    }

    public static function timePop($field, $value, $allow_zero){
        if(!$allow_zero && empty($value)){
            $time = explode("/", date("h/i/a", time()));
            $h = $time[0];
            $i = $time[1];
            $a = $time[2];
            if($a == 'PM') $h += 12;
            $value = ($h * 60) + $i;
        } else {
            $i = $value % 60;
            $h = ($value - $i) / 60;
            if($h > 12){
                $a = "PM";
                $h -= 12;
            } else {
                $a = "AM";
            }
        }

        $output = self::hourPop($field."_h", $h, $allow_zero) . ':';
        $output .= self::minutePop($field . '_i', empty($value) ? '' : $i, $allow_zero);
        $output .= ' ' . self::APPop($field . '_a', $a, $allow_zero);
        return $output;
    }

    public static function dateTimePop($field, $value, $allow_zero, $first_year = 0){
        if(!$allow_zero && empty($value)) {
            $value = time();
        }

        if (empty($value)) {
            $time = array(0,0,0,0,0,0,0);
        } else {
            $date = new DateTime('@' . $value, new DateTimeZone('UTC'));
            $time = explode('/', $date->format('m/d/Y/h/i/s/a'));
        }
        $output = self::monthPop($field."_m", $time[0], $allow_zero, '', array('class' => 'dateTimePop')) . ' / ';
        $output .= self::dayPop($field."_d", $time[1], $allow_zero, array('class' => 'dateTimePop')) . ' / ';
        $output .= self::yearPop($field."_y", $time[2], $allow_zero, $first_year, null, array('class' => 'dateTimePop')) . ' at ';
        $output .= self::hourPop($field."_h", $time[3], $allow_zero, array('class' => 'dateTimePop')) . ':';
        $output .= self::minutePop($field."_i", empty($value) ? null : $time[4], $allow_zero, array('class' => 'dateTimePop')) . ' ';
        $output .= self::APPop($field."_a", $time[6], $allow_zero, array('class' => 'dateTimePop'));
        return $output;
    }

    public static function hourPop($field, $value = '', $allow_zero = false, $attributes = array()){
        $values = array();
        if ($allow_zero) {
            $values[''] = '';
        }
        $values += array_combine(range(1, 12), range(1, 12));

        // Set the default class.
        BasicHTML::setDefaultClass($attributes, 'timePop');

        // TODO: Pass the class into this renderer.
        return BasicHTML::select($field, $values, intval($value), $attributes);
    }

    /**
     * Build a popup selector for minutes.
     *
     * @param string $field
     *   The field name.
     * @param string $value
     *   The default value.
     * @param boolean $allow_zero
     *   Whether to allow the field to be blank.
     * @param array $attributes
     *   An array of attributes to add to the element..
     *
     * @return string
     *   The rendered HTML.
     */
    public static function minutePop($field, $value = '', $allow_zero = false, $attributes = array()){
        $values = array();
        if ($allow_zero) {
            $values[''] = '';
        }
        $values += array(0 => '00', 15 => 15, 30 => 30, 45 => 45);

        // Set the default class.
        BasicHTML::setDefaultClass($attributes, 'timePop');

        // TODO: Pass the class into this renderer.
        return BasicHTML::select($field, $values, intval($value), $attributes);
    }

    /**
     * Build a popup to select AM/PM
     *
     * @param string $field
     *   The field name.
     * @param string $value
     *   The default value.
     * @param boolean $allow_zero
     *   Whether to allow the field to be blank.
     * @param array $attributes
     *   An array of attributes to add to the element..
     *
     * @return string
     *   The rendered HTML
     */
    public static function APPop($field, $value = '', $allow_zero = false, $attributes = array()){
        $values = array();
        if ($allow_zero) {
            $values[''] = '';
        }
        $values += array('AM' => 'AM', 'PM' => 'PM');

        // Set the default class.
        BasicHTML::setDefaultClass($attributes, 'timePop');

        // TODO: Pass the class into this renderer.
        return BasicHTML::select($field, $values, strtoupper($value), $attributes);
    }

    public static function dayPop($field, $day=0, $allow_zero = false, $attributes = array()){
        $values = array();
        if ($allow_zero) {
            $values[''] = '';
        }
        $values += array_combine(range(1, 31), range(1, 31));

        // Set the default class.
        BasicHTML::setDefaultClass($attributes, 'datePop');

        // TODO: Pass the class into this renderer.
        return BasicHTML::select($field, $values, intval($day), $attributes);
    }

    public static function monthPop($field, $month = 0, $allow_zero = false, $attributes = array()){
        $values = array();
        if ($allow_zero) {
            $values[''] = '';
        }
        $info = cal_info(0);
        $values += $info['months'];

        // Set the default class.
        BasicHTML::setDefaultClass($attributes, 'datePop');

        // TODO: Pass the class into this renderer.
        return BasicHTML::select($field, $values, intval($month), $attributes);
    }

    public static function yearPop($field, $year = 0, $allow_zero = false, $first_year = null, $last_year = null, $attributes = array()){
        $values = array();
        if ($allow_zero) {
            $values[''] = '';
        }

        if (empty($first_year)) {
            $first_year = date('Y') - 1;
        }
        if (empty($last_year)) {
            $last_year = date('Y') + 10;
        }

        $values += array_combine(range($first_year, $last_year), range($first_year, $last_year));

        // Set the default class.
        BasicHTML::setDefaultClass($attributes, 'datePop');

        // TODO: Pass the class into this renderer.
        return BasicHTML::select($field, $values, intval($year), $attributes);
    }
}
