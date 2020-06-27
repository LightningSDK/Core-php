<?php

namespace lightningsdk\core\View\Field;

use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\View\HTML;

class BasicHTML {

    /**
     * Build a select/option field.
     *
     * @param string $name
     *   The name and ID of the field.
     * @param array $values
     *   A keyed array of option/value combinations.
     *
     * This array can be a simple array for a single select field with numeric keys:
     *   ['option 1', 'option 2', 'option 3']
     * With specific keys:
     *   ['value1' => 'option 1', 'value2' => 'option 2']
     * With hierarchical option groups:
     *   ['Group 1' => [
     *     'option1' => 'Label 1',
     *     'option2' => 'Label 2',
     *
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
        $attributes['class'] = ($attributes['class'] ?? '') . ' radio-group';
        $output = '<div ' . HTML::implodeAttributes($attributes) . '>';

        $required = !empty($attributes['required']) ? 'required ' : '';
        foreach ($options as $value => $label) {
            $checked = $default === $value ? 'CHECKED="checked" ' : '';
            $output .= '<label><input type="radio" name="' . $name . '" value="' . $value . '" ' . $checked . $required . ' /> <span>' . $label . '</span></label>';
        }

        return $output . '</div>';
    }

    public static function checkbox($name, $default = false, $attributes = []) {
        $field = '<label><input type="checkbox" name="' . $name . '" value="1" ' . ($default ? 'CHECKED ' : '') . (!empty($attributes['required']) ? 'REQUIRED ': '') . ' /> ' . $attributes['label'] . '</label>';

        if (!empty($attributes['required'])) {
            $error_message = $attributes['error'] ?? 'This field is required.';
            $field = '<div>' . $field . '<small class="form-error">' . $error_message . '</small></div>';
        }

        return $field;
    }

    public static function checkboxGroup($name, $options, $default = null, $attributes = []) {
        $output = '<div ' . HTML::implodeAttributes($attributes) . '>';

        foreach ($options as $value => $label) {
            $checked = $default === $value ? 'CHECKED="checked"' : '';
            $output .= '<label><input type="checkbox" name="' . $name . '[]" value="' . $value . '" ' . $checked . ' /> ' . $label . '</label>';
        }

        return $output . '</div>';
    }

    public static function text($id, $value = '', $attributes = []) {
        // This only applies to text fields.
        if (empty($attributes['max_length']) && !empty($attributes['size'])) {
            $attributes['max_length'] = $attributes['size'];
        }
        $attributes['name'] = $id;
        $attributes['id'] = $id;
        $attributes['value'] = $value;
        $attributes['type'] = 'text';
        $field = '<input ' . HTML::implodeAttributes($attributes) . ' />';
        if (!empty($attributes['required']) || !empty($attributes['error'])) {
            $error_message = $attributes['error'] ?? 'This field is required.';
            $field = '<div>' . $field . '<small class="form-error">' . $error_message . '</small></div>';
        }

        return $field;
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

        // Auto complete should default off for hidden fields.
        if (!array_key_exists('autocomplete', $attributes)) {
            $attributes['autocomplete'] = 'off';
        }

        return '<input ' . HTML::implodeAttributes($attributes) . ' />';
    }

    public static function submit($content, $options = []) {
        $attributes = [
            'type' => 'submit',
            'value' => 'submit',
        ] + $options;

        if (empty($attributes['class'])) {
            $attributes['class'] = 'button';
        }

        return '<button ' . HTML::implodeAttributes($attributes) . ' />' . $content . '</button>';
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
