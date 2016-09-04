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

    public static function implodeStyles($styles) {
        $flat = '';
        foreach ($styles as $k => $v) {
            $flat .= ';' . $k . ':' . $v;
        }
        return $flat;
    }

    public static function getFirstImage($html) {
        preg_match_all('/<img\s+.*?src=[\"\']?([^\"\' >]*)[\"\']?[^>]*>/i', $html, $matches, PREG_SET_ORDER);
        if(!empty($matches[0][1])) {
            return (file_exists(HOME_PATH.$matches[0][1])) ? $matches[0][1] : NULL;
        }
        return null;
    }
}