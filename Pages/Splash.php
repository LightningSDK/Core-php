<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Form;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\View\CSS;
use lightningsdk\core\View\JS;
use lightningsdk\core\View\Page;

class Splash extends Page {

    protected function hasAccess() {
        return true;
    }

    public function get() {
        $page = Request::getLocation();

        $splash_settings = Configuration::get('splash.pages.' . $page);

        // No template found.
        if (empty($splash_settings) || (is_array($splash_settings) && empty($splash_settings['page']))) {
            $this->page = $page;
        }

        // Set the template.
        else {
            $this->page = is_array($splash_settings) ? $splash_settings['page'] : $splash_settings;
        }

        $this->updateSettings($splash_settings);

        // Add any CSS or JS files.
        if (is_array($splash_settings)) {
            if (!empty($splash_settings['css'])) {
                CSS::add($splash_settings['css']);
            }
            if (!empty($splash_settings['js'])) {
                JS::add($splash_settings['js']);
            }
        }
    }
}
