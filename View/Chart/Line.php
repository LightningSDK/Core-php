<?php

namespace Lightning\View\Chart;

use Lightning\Tools\Request;
use Lightning\View\Field\BasicHTML;
use Lightning\View\JS;

class Line extends Base {
    protected $renderer = 'Line';

    public function __construct() {
        parent::__construct();
        JS::set('chart.' . $this->id . '.url', '/' . Request::get('request'));
        JS::set('chart.' . $this->id . '.params.start', ['source' => 'start']);
        JS::set('chart.' . $this->id . '.params.number_format', $this->numberFormat);
    }

    public function renderControls() {
        return BasicHTML::select('start', [
            -30 => 'Last 30 Days',
            -60 => 'Last 60 Days',
            -90 => 'Last 90 Days',
        ]);
    }
}
