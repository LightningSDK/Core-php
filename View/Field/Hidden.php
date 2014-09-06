<?php

namespace Lightning\View\Field;

class Hidden {
    public static function render($name, $value = '') {
        return '<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $value . '" />';
    }
}
