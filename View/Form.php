<?php

namespace Lightning\View;

class Form {
    public static function renderMarkup($options, $vars) {
        // If this is the closing form tag.
        if (isset($options['end'])) {
            return '</form>';
        }

        // Render the open form tag.
        $action = empty($options['action']) ? '/contact' : $options['action'];
        $output = '<form action="' . $action . '" method="POST">';
        \Lightning\Tools\Form::requiresToken();
        $output .= \Lightning\Tools\Form::renderTokenInput();

        // Loop through basic, unedited options.
        foreach (['list', 'contact', 'success', 'redirect'] as $option) {
            if (isset($options[$option])) {
                $output .= '<input type="hidden" name="list" value="' . $options[$option] . '">';
            }
        }

        return $output;
    }
}
