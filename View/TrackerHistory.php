<?php

namespace Lightning\View;

use Lightning\Tools\Tracker;

class TrackerHistory {

    protected $tracker;
    protected $start;
    protected $end;
    protected $sub_id;
    protected $user_id;

    public function __construct($tracker, $start = -30, $end = 0, $sub_id = -1, $user_id = -1) {
        $this->tracker = $tracker;
        $this->start = $start;
        $this->end = $end;
        $this->sub_id = $sub_id;
        $this->user_id = $user_id;
    }

    public function render() {
        // Add the JS.
        JS::add('/js/chart.min.js');

        // Build the required HTML elements.

        // Add the data to the page.
//        $statistics = Tracker::getHistory($this->tracker, $this->start, $this->end, $this->sub_id, $this->user_id);
    }
} 