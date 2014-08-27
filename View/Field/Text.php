<?php

namespace Lightning\View\Field;

use Lightning\View\Field;
use Lightning\View\HTML;

class Text extends Field {
    /**
     * Build a text field.
     *
     * @param $id
     * @param $value
     * @param array $options
     *
     * @return string
     *   The rendered HTML.
     */
    public static function textField($id, $value, $options = array()) {
        $attributes['class'] = !empty($options['classes']) ? empty($options['classes']) : array();
        if (isset($options['autocomplete']) && empty($options['autocomplete'])) {
            $attributes['autocomplete'] = 'off';
            $attributes['class'][] = 'table_autocomplete';
        }
        if (!empty($options['size'])) {
            $attributes['max_length'] = $options['size'];
        }
        $attributes['name'] = $id;
        $attributes['id'] = $id;
        $attributes['value'] = $value;
        $attributes['type'] = 'text';
        return '<input ' . HTML::implodeAttributes($attributes) . ' />';
    }
}
