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

        $params = $_GET;
        unset($params['request']);
        $params = ['action' => 'body'] + $params;

        echo 'document.write(\'<iframe frameborder="0" src="' . Configuration::get('web_root') . '/' . $widget . '?' . http_build_query($params) . '" id="' . $id . '" width="100%"></iframe>\');
        if (typeof lightning != "undefined") {
            document.write(\'<script src="' . Configuration::get('web_root') . '/js/lightning.min.js"></script>\');
        }
        if (typeof lightning_startup != "undefined") {
            lightning_startup(function(){lightning.widget.initIframe(\'' . $id . '\');});
        }
        else if (typeof $_startup != "undefined") {
            $_startup(function(){lightning.widget.initIframe(\'' . $id . '\');}, "lightning");
        }';
        exit;
    }

    public function getBody() {
        JS::startup('lightning.widget.initBody()');
    }

}
