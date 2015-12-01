<?php

namespace Lightning\View;

use Lightning\Tools\Request;
use Lightning\Tools\Scrub;

class Field {
    public function render ($field) {
        return '';
    }

    /**
     * Get the encoded default value for a form element.
     *
     * @param string $var
     *   The name of the field.
     * @param string $alt_default
     *   A default if nothing was submitted.
     * @param string $type
     *   The type, usually html ot text.
     *
     * @return string
     *   The HTML encoded value.
     */
    public static function defaultValue($var, $alt_default = null, $type = 'text') {
        $default = Request::get($var, $type) !== null ? Request::get($var, $type) : $alt_default;
        return Scrub::toHTML($default);
    }
}
