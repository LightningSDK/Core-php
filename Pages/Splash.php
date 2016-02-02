<?php

namespace Lightning\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Request;
use Lightning\View\CSS;
use Lightning\View\JS;
use Lightning\View\Page;

class Splash extends Page {

    protected function hasAccess() {
        return true;
    }

    public function get() {
        $page = Request::getLocation();

        $template_page = Configuration::get('splash.pages.' . $page);

        // No template found.
        if (empty($template_page) || (is_array($template_page) && empty($template_page['page']))) {
            $this->page = $page;
        }

        // Set the template.
        else {
            $this->page = is_array($template_page) ? $template_page['page'] : $template_page;
        }

        if (!empty($template_page['template'])) {
            $this->template = $template_page['template'];
        }

        // Add any CSS or JS files.
        if (is_array($template_page)) {
            if (!empty($template_page['css'])) {
                CSS::add($template_page['css']);
            }
            if (!empty($template_page['js'])) {
                JS::add($template_page['js']);
            }
        }
    }

}
