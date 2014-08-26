<?php

namespace Lightning\View\Field;

class BasicHTML {
    public static function select($name, $values, $default) {
        $return = '<select name="' . $name . '" id="' . $name . '" >';
        foreach ($values as $value => $label) {
            $return .= '<option value="' . $value . '">' . $label . '</option>';
        }
        $return .= '</select>';
        return $return;
    }
}
