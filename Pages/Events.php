<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\Page;
use lightningsdk\core\Model\Calendar;

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
