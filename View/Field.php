<?php

namespace lightningsdk\core\View;

use lightningsdk\core\Tools\ReCaptcha;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Scrub;

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

    /**
     * Markup renderer for inline templating.
     *
     * @param array $options
     *   - placeholder - string - For text fields, this is a preview within the text field.
     *   - name - string - The field name
     *   - value - string - The default value
     *   - type - string - The field type. Can be text, password, checkbox, radio
     *   - class - string - Classes to add to the field.
     *
     * @return string
     *   Rendered HTML
     */
    public static function renderMarkup($options) {
        $attributes = [
            'placeholder' => !empty($options['placeholder']) ? $options['placeholder'] : '',
            'name' => !empty($options['name']) ? $options['name'] : '',
            'value' => !empty($options['value']) ? $options['value'] : '',
            'type' => !empty($options['type']) ? $options['type'] : 'text',
            'class' => !empty($options['class']) ? $options['class'] : '',
            'style' => !empty($options['style']) ? $options['style'] : '',
        ];

        if ($attributes['type'] == 'invisibleRecaptcha') {
            return ReCaptcha::renderInvisible($attributes['value'], $attributes['class']);
        }

        if (!empty($options['type']) && $options['type'] == 'submit' && empty($attributes['name'])) {
            $attributes['name'] = 'submit';
        }

        if (isset($options['required'])) {
            $attributes['required'] = '';
        }

        if (!empty($options['pattern'])) {
            $attributes['pattern'] = $options['pattern'];
        }

        $field = '<label>';
        if (!empty($options['label'])) {
            $field .= $options['label'];
        }

        $field .= '<input ' . HTML::implodeAttributes($attributes) . '>';

        if (!empty($attributes['required']) || !empty($options['error'])) {
            $error_message = $options['error'] ?? 'This field is required.';
            $field = $field . '<span class="form-error">' . $error_message . '</span>';
        }
        $field .= '</label>';

        return '<div>' . $field . '</div>';
    }
}
