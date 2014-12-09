<?php

namespace Lightning\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\Page;

class Splash extends Page {

    public function get() {
        $page = Request::get('request');

        $template_page = Configuration::get('splash.pages.' . $page);

        if (empty($template_page)) {
            Output::error('Page not found.');
        }

        else {
            $this->page = $template_page;
        }
    }

}
