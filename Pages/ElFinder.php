<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\Tools\ClientUser;

class ElFinder extends \lightningsdk\core\View\Page {

    protected $template = ['elfinder', 'lightningsdk/core'];

    public function hasAccess(){
        return ClientUser::requireAdmin();
    }

    public function get() {

    }
}
