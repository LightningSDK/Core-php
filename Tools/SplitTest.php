<?php

namespace Lightning\Tools;

use Lightning\Model\Tracker;
use Lightning\View\JS;
use stdClass;

class SplitTest {

    protected static $options = [];

    /**
     * Fills in content with randomly select split test content.
     *
     * @param string $name
     *   The name of the split test. If multiple tests use the same name,
     *   they will always use the same index of the options.
     * @param array $options
     *   A list of options
     * @param boolean $sticky
     *   Whether the options should be stuck to the session.
     *   This means realoading the page will yield the same content.
     *
     * @return string
     *   The rendered content.
     */
    public static function render($name, $options, $sticky = true) {
        $session = Session::getInstance();

        if (array_key_exists($name, self::$options)) {
            $option = self::$options[$name];
        } else {
            if ($sticky && !empty($session->content->splitTest->{$name})) {
                $option = $session->content->splitTest->{$name};
            } else {
                $option_number = rand(1, count($options)) - 1;
                $option_names = array_keys($options);
                $option = $option_names[$option_number];
                if ($sticky) {
                    if (empty($session->content->splitTest)) {
                        $session->content->splitTest = new stdClass();
                    }
                    $session->content->splitTest->{$name} = $option;
                    $session->save();
                }

            }
            // Track the usage.
            $split_test = \Lightning\Model\SplitTest::loadOrCreateByLocator($name);
            $tracker = Tracker::loadOrCreateByName($name, 'Split Test');
            $tracker->track($split_test->id);

            // Save the split reference.
            self::$options[$name] = $option;
        }

        $option_value = $options[$option];

        JS::set('splitTest.' . $name, $option);

        return is_callable($option_value) ? $option_value() : $option_value;
    }
}
