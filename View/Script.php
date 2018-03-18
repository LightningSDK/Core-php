<?php

namespace Lightning\View;

use Exception;

class Script {
    /**
     * Markup renderer for inline templating.
     *
     * @param array $options
     *
     * @return string
     *   Rendered HTML
     *
     * @throws Exception
     */
    public static function renderMarkup($options) {

        if (empty($options['src'])) {
            throw new Exception('Missing iFrame source.');
        }

        $attributes = [
            'src' => !empty($options['src']) ? $options['src'] : '',
        ];

        return '<script ' . HTML::implodeAttributes($attributes) . '></script>';
    }
}
