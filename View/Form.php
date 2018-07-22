<?php

namespace Lightning\View;

use Lightning\View\Field\BasicHTML;

class Form {
    public static function renderMarkup($options, $vars) {
        // If this is the closing form tag.
        if (isset($options['end'])) {
            return '</form>';
        }

        // Render the open form tag.
        $form_attributes = [
            'method' => 'POST',
            isset($options['ajax']) ? 'data-ajax-action' : 'action' => $options['action'] ?? '/contact',
        ];
        if (isset($options['abide'])) {
            $form_attributes['data-abide'] = isset($options['ajax']) ? 'ajax' : '';
        }

        foreach ($options as $key => $val) {
            if (preg_match('/^data-/', $key)) {
                $form_attributes[$key] = $val;
            }
        }

        $output = '<form ' . HTML::implodeAttributes($form_attributes) . '>';
        \Lightning\Tools\Form::requiresToken();
        $output .= \Lightning\Tools\Form::renderTokenInput();

        // Loop through basic, unedited options.
        foreach (['list', 'contact', 'success', 'redirect', 'message'] as $option) {
            if (isset($options[$option])) {
                $output .= '<input type="hidden" name="' . $option . '" value="' . $options[$option] . '">';
            }
        }

        return $output;
    }

    public static function render($form) {
        $form_attributes = [
            'action' => $form['action'],
            'method' => $form['method'] ?? 'POST',
        ];

        if (!empty($form['validate'])) {
            $form_attributes['data-abide'] = '';
        }

        $output = '<form ' . HTML::implodeAttributes($form_attributes) . '>';
        \Lightning\Tools\Form::requiresToken();
        $output .= \Lightning\Tools\Form::renderTokenInput();

        foreach ($form['fields'] as $field) {
            $output .= '<div class="field ' . ($field['type']??'') . '">';
            if (!empty($field['label']) && $field['type'] != 'checkbox') {
                $output .= '<div class="field-label">' . $field['label'] . '</div>';
            }
            $options = [];
            if (!empty($field['required'])) {
                $options['required'] = true;
            }
            switch ($field['type']) {
                case 'hidden':
                    $output .= BasicHTML::hidden($field['name'], $field['value']);
                    break;
                case 'radios':
                    $output .= BasicHTML::radioGroup($field['name'], $field['options'], $field['default'] ?? null, ['required' => !empty($field['required'])]);
                    break;
                case 'checkbox':
                    $output .= BasicHTML::checkbox($field['name'], !empty($field['default']), ['label' => $field['label']] + $options);
                    break;
                case 'email':
                    $output .= BasicHTML::text($field['name'], '', ['type' => 'email'] + $options);
                    break;
                case 'submit':
                    $output .= BasicHTML::submit($field['value'], ['classes' => $field['classes'] ?? 'button medium btn-blue']);
                    break;
            }
            if (!empty($field['note'])) {
                $output .= '<div class="field-note">' . $field['note'] . '</div>';
            }
            $output .= '</div>';
        }

        $output .= '</form>';

        return $output;
    }
}
