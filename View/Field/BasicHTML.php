<?php

namespace Lightning\View\Field;

use Lightning\Tools\Scrub;
use Lightning\View\HTML;

class BasicHTML {

    /**
     * Build a select/option field.
     *
     * @param string $name
     *   The name and ID of the field.
     * @param $values
     *   A keyed array of option/value combinations.
     * @param string|integer $default
     *   The default selected value.
     * @param array $attributes
     *   An array of additional attributes (class, onclick, etc)
     *
     * @return string
     *   The rendered HTML.
     */
    public static function select($name, $values, $default = null, $attributes = []) {
        // Add any attributes.
        $attribute_string = HTML::implodeAttributes($attributes);

        // Build the main tag.
        $select_name = !empty($attributes['multiple']) ? $name . '[]' : $name;
        $return = '<select name="' . $select_name . '" id="' . $name . '" ' . $attribute_string . '>';
        // Iterate over each option.
        $return .= self::renderSelectOptions($values, $default);
        $return .= '</select>';
        return $return;
    }

    protected static function renderSelectOptions($values, $default) {
        $return = '';
        foreach ($values as $value => $label) {
            // Set this value selected if it's the default value.
            if (is_array($label)) {
                $return .= '<optgroup label="' . $value . '">';
                $return .= self::renderSelectOptions($label, $default);
                $return .= '</optgroup>';
            }
            else {
                if (
                    (is_numeric($value) && $value > 0 && $value == $default)
                    || $value === $default
                    || (is_array($default) && in_array($value, $default))
                ) {
                    $selected = 'SELECTED="selected"';
                } else {
                    $selected = '';
                }
                $return .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
            }
        }
        return $return;
    }

    public static function radioGroup($name, $options, $default = null, $attributes = []) {
        $output = '<div ' . HTML::implodeAttributes($attributes) . '>';

        $required = !empty($attributes['required']) ? 'required ' : '';
        foreach ($options as $value => $label) {
            $checked = $default === $value ? 'CHECKED="checked" ' : '';
            $output .= '<label><input type="radio" name="' . $name . '" value="' . $value . '" ' . $checked . $required . ' /> <span>' . $label . '</span></label>';
        }

        return $output . '</div>';
    }

    public static function checkboxGroup($name, $options, $default = null, $attributes = []) {
        $output = '<div ' . HTML::implodeAttributes($attributes) . '>';

        foreach ($options as $value => $label) {
            $checked = $default === $value ? 'CHECKED="checked"' : '';
            $output .= '<label><input type="checkbox" name="' . $name . '[]" value="' . $value . '" ' . $checked . ' /> ' . $label . '</label>';
        }

        return $output . '</div>';
    }

    public static function text($id, $value, $attributes = []) {
        // This only applies to text fields.
        if (empty($attributes['max_length']) && !empty($attributes['size'])) {
            $attributes['max_length'] = $attributes['size'];
        }
        $attributes['name'] = $id;
        $attributes['id'] = $id;
        $attributes['value'] = $value;
        $attributes['type'] = 'text';
        return '<input ' . HTML::implodeAttributes($attributes) . ' />';
    }

    public static function password($id, $value, $options = []) {
        if (empty($options['max_length']) && !empty($options['size'])) {
            $options['max_length'] = $options['size'];
        }
        $options['name'] = !empty($options['name']) ? $options['name'] : $id;
        $options['id'] = !empty($options['id']) ? $options['id'] : $id;
        $options['value'] = $value;
        $options['type'] = 'password';
        $options['autocomplete'] = 'off';
        return '<input ' . HTML::implodeAttributes($options) . ' />';
    }

    public static function textarea($id, $value, $attributes) {
        $attributes['name'] = !empty($options['name']) ? $attributes['name'] : $id;
        $attributes['id'] = !empty($options['id']) ? $attributes['id'] : $id;
        return '<textarea ' . HTML::implodeAttributes($attributes) . ' >' . Scrub::toHTML($value) . '</textarea>';
    }

    public static function hidden($name, $value = '', $attributes = []) {
        // This only applies to text fields.
        $attributes['name'] = $name;
        $attributes['id'] = $name;
        $attributes['value'] = $value;
        $attributes['type'] = 'hidden';
        return '<input ' . HTML::implodeAttributes($attributes) . ' />';
    }

    /**
     * Set the default class for a set of attributes.
     *
     * @param array $attributes
     *   An attribute array.
     * @param $default
     *   The default class to add if no classes are set.
     */
    public static function setDefaultClass(&$attributes, $default) {
        if (empty($attributes['class'])) {
            $attributes['class'] = [$default];
        } elseif (!is_array($attributes['class'])) {
            $attributes['class'] = [$attributes['class']];
        } elseif (!in_array('datePop', $attributes['class'])) {
            $attributes['class'][] = $default;
        }
    }
}
