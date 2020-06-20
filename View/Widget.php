<?php

namespace lightningsdk\core\View;

use Exception;
use lightningsdk\core\Model\Widget as WidgetModel;

class Widget {
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

        if (!empty($options['name'])) {
            $widget = WidgetModel::loadByName($options['name']);
        }

        else if (!empty($options['id'])) {
            $widget = WidgetModel::loadByID($options['id']);
        }

        if (empty($widget)) {
            throw new Exception('Widget not found');
        }

        return '<div class="widget">' . $widget->content . '</div>';
    }
}
