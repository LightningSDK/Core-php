<?php

namespace Lightning\View;

use Lightning\Tools\Scrub;

class HTML {
    public static function implodeAttributes($attributes) {
        $output = '';
        foreach ($attributes as $name => &$value) {
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $output .= $name . '="' . Scrub::toHTML($value) . '" ';
        }
        return $output;
    }
}