<?php

namespace Lightning\View;

use Lightning\Tools\Configuration;
use Lightning\Tools\Request;

class Widget extends Page {

    protected $template = ['widget_template', 'Lightning'];
    protected $widget = null;

    public function hasAccess() {
        return true;
    }

    /**
     * This outputs the initialization JS
     */
    public function get() {
        $id = Request::get('id', Request::TYPE_STRING, null, 'lightning_widget');
        $widget = empty($this->widget) ? Request::get('widget') : $this->widget;
        echo 'document.write(\'<iframe frameborder="0" src="' . Configuration::get('web_root') . '/' . $widget . '?action=body" id="' . $id . '"></iframe>\');
        if (!lightning || !lightning.widget) {
            document.write(\'<script src="' . Configuration::get('web_root') . '/js/lightning.min.js"></script>\');
        }
        lightning_startup(function(){lightning.widget.initIframe("' . $id . '");});';
        exit;
    }

    public function getBody() {
        JS::startup('lightning.widget.initBody()');
    }

}
