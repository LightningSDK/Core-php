<?php

namespace Lightning\Tools;

class SplitTest {
    public static function render($name, $options) {
        Session::getInstance();
        $option = rand(1, count($options)) - 1;
        $option_names = array_keys($options);
        $option_name = $option_names[$option];
        $option_value = $options[$option_name];

        return is_callable($option_value) ? $option_value() : $option_value;
    }
}
