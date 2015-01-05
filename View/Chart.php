<?php

namespace Lightning\View;

use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\Field\BasicHTML;
use Lightning\View\Field\Time;

abstract class Chart extends Page {
    protected $page = 'chart';
    protected $width = '600';
    protected $height = '450';
    protected $numberFormat = 'float';
    public $id = 'chart';

    public function __construct() {
        parent::__construct();
        JS::add('/js/Chart.min.js');
        JS::set('chart.' . $this->id . '.url', '/' . Request::get('request'));
        JS::startup("lightning.stats.init()");
        JS::startup("lightning.stats.updateStats('" . $this->id . "')");
        JS::set('chart.' . $this->id . '.params.start', ['source' => 'start']);
        JS::set('chart.' . $this->id . '.params.number_format', $this->numberFormat);
    }

    public function get() {
        Template::getInstance()->set('chart', $this);
    }

    public function renderControls() {
        return BasicHTML::select('start', [
            -30 => 'Last 30 Days',
            -60 => 'Last 60 Days',
            -90 => 'Last 90 Days',
        ]);
    }

    public function renderCanvas() {
        return '<canvas id="' . $this->id . '" height="' . $this->height . '" width="' . $this->width . '"></canvas>';
    }
}
