<?php

namespace Lightning\View;

use Lightning\Tools\Scrub;

class HTML {
    public static function implodeAttributes($attributes) {
        // Normalize some basic attributes.
        if (!empty($attributes['classes'])) {
            $attributes['class'] = $attributes['classes'];
            unset($attributes['classes']);
        }
        if (isset($attributes['autocomplete'])) {
            $attributes['autocomplete'] = empty($options['autocomplete']) ? 'off' : 'on';
        }

        $attribute_string = '';
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $attribute_string .= $key . '="' . Scrub::toHTML($value) . '" ';
        }

        return $attribute_string;
    }
}