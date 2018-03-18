<?php

namespace Lightning\View;

use Exception;

class Iframe {
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

        return '<iframe ' . HTML::implodeAttributes($attributes) . '></iframe>';
    }
}
