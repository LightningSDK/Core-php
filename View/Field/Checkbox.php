<?php

namespace lightningsdk\core\View\Field;

use lightningsdk\core\View\HTML;

class Checkbox {
    public static function render($name, $value = '', $checked = false, $attributes = []) {
        $attributes += [
            'type' => 'checkbox',
            'value' => $value,
            'name' => $name,
            'id' => $name,
        ];
        if ($checked) {
            $attributes['checked'] = 'CHECKED';
        }
        return '<input ' . HTML::implodeAttributes($attributes) . ' />';
    }
}
