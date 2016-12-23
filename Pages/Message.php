<?php

namespace Lightning\Pages;

use Lightning\View\Page;

class Message extends Page {

    protected $rightColumn = false;
    protected $share = false;

    protected function hasAccess() {
        return true;
    }
}
