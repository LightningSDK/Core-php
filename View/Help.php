<?php

namespace Lightning\View;

use Lightning\Tools\Scrub;

class Help {
    /**
     * Print a question mark with a tool tip.
     *
     * @param $help_string
     * @param string $image
     * @param string $id
     * @param string $class
     * @param null $url
     *
     * @todo this needs JS injection and should be moved to a view.
     */
    public static function render($help_string, $image = '/images/qmark.png', $id = '', $class = '', $url = NULL) {
        if ($url) {
            echo '<a href="' . $url . '">';
        }
        echo '<img data-tooltip aria-haspopup="true" class="has-tip" title="' . Scrub::toHTML($help_string) . '" src="' . $image . '" border="0" class="help tooltip ' . $class . '" id="' . $id . '" />';
        if ($url) {
            echo '</a>';
        }
    }
}
