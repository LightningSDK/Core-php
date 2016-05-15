<?php

namespace Lightning\Pages;

use Lightning\Tools\Template;
use Lightning\View\Page;
use Lightning\Model\Calendar;

class Events extends Page {
    public $page = 'calendar_page';
    protected $menuContext = 'events';

    public function hasAccess() {
        return true;
    }

    public function __construct() {
        Template::getInstance()->set('full_width', true);
        parent::__construct();
    }

    public function get() {
        Template::getInstance()->set('calendar', new Calendar());
    }
}
