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
    public static function select($name, $values, $default = null, $attributes = array()) {
        // Add any attributes.
        $attribute_string = '';
        foreach ($attributes as $key => $attribute) {
            $attribute_string .= ' ' . $key . '="' . (is_array($attribute) ? implode(' ', $attribute) : $attribute) . '"';
        }

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

    public static function radioGroup($name, $options, $default = null, $attributes = array()) {
        $output = '<div ' . HTML::implodeAttributes($attributes) . '>';

        foreach ($options as $value => $label) {
            $output .= '<label><input type="radio" name="' . $name . '" value="' . $value . '" /> ' . $label . '</label>';
        }

        return $output . '</div>';
    }

    public static function password($id, $value, $options = array()) {
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
            $attributes['class'] = array($default);
        } elseif (!is_array($attributes['class'])) {
            $attributes['class'] = array($attributes['class']);
        } elseif (!in_array('datePop', $attributes['class'])) {
            $attributes['class'][] = $default;
        }
    }
}
