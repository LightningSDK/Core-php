<?php

namespace Lightning\View;

use Lightning\Tools\Request;
use Lightning\Tools\Template;

abstract class Chart extends Page {
    protected $page = 'chart';
    protected $width = '600';
    protected $height = '450';
    protected $id = 'chart';

    public function __construct() {
        parent::__construct();
        JS::add('/js/Chart.min.js');
        JS::set('chart.' . $this->id . '.url', '/' . Request::get('request'));
        JS::startup("lightning.stats.updateStats('" . $this->id . "')");
    }

    public function get() {
        Template::getInstance()->set('chart', $this);
    }

    public function renderControls() {

    }

    public function renderCanvas() {
        return '<canvas id="' . $this->id . '" height="' . $this->height . '" width="' . $this->width . '"></canvas>';
    }
}
