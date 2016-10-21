<?php

namespace Lightning\View;

class Form {
    public static function renderMarkup($options, $vars) {
        // If this is the closing form tag.
        if (isset($options['end'])) {
            return '</form>';
        }

        // Render the open form tag.
        $form_attributes = [
            'method' => 'POST',
            'action' => empty($options['action']) ? '/contact' : $options['action'],
        ];
        if (isset($options['abide'])) {
            $form_attributes['data-abide'] = '';
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
}
