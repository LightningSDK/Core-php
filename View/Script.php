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

        if (empty($options['src']) && empty($options['body'])) {
            throw new Exception('Missing script source or body.');
        }

        $attributes = [];

        if (!empty($options['src'])) {
            $attributes['src'] = $options['src'];
        }

        $body = !empty($options['body']) ? $options['body'] : '';

        return '<script ' . HTML::implodeAttributes($attributes) . '>' . $body . '</script>';
    }
}
