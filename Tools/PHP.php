<?php

namespace Lightning\Tools;

class PHP {
    public static function arrayPropertySum(&$array, $property) {
        $value = 0;
        foreach ($array as $a) {
            if (!empty($a[$property])) {
                $value += $a[$property];
            }
        }
        return $value;
    }

    public static function getArrayPropertyValues(&$array, $property) {
        $values = [];
        foreach ($array as $item) {
            $values[] = $item['property'];
        }
        return $values;
    }

    public static function rekeyByProperty(&$array, $property, $old_key_to_new_property = null) {
        $new_array = [];
        foreach ($array as $old_key => $item) {
            if (!empty($old_key_to_new_property)) {
                $item[$old_key_to_new_property] = $old_key;
            }
            $new_array[$item[$property]] = $item;
        }
        return $new_array;
    }

    public static function arraySortByNumericProperty(&$array, $property) {
        self::$property = $property;
        usort($array, 'self::compareNumeric');
    }

    public static function arrayRSortByNumericProperty(&$array, $property) {
        self::$property = $property;
        usort($array, 'self::compareNumericReverse');
    }

    protected static function compareNumeric($a1, $a2) {
        if ($a1[self::$property] < $a2[self::$property]) {
            return -1;
        } elseif ($a1[self::$property] > $a2[self::$property]) {
            return 1;
        } else {
            return 0;
        }
    }

    protected static function compareNumericReverse($a1, $a2) {
        return - self::compareNumeric($a1, $a2);
    }
}
