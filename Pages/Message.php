<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\View\Page;

class Message extends Page {

    protected $rightColumn = false;
    protected $share = false;

    protected function hasAccess() {
        return true;
    }

    public function get() {
        
    }
}
