<?php

namespace Lightning\View\Field;

use Lightning\View\HTML;

class Checkbox {
    public static function render($name, $value = '', $checked = false, $attributes = []) {
        $attributes += array(
            'type' => 'checkbox',
            'value' => $value,
            'name' => $name,
            'id' => $name,
        );
        if ($checked) {
            $attributes['checked'] = 'CHECKED';
        }
        return '<input ' . HTML::implodeAttributes($attributes) . ' />';
    }
}
