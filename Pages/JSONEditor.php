<?php

namespace Lightning\Pages;

use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\JS;

abstract class JSONEditor extends Page {
    public $page = 'json_editor';

    public function get() {
        Template::getInstance()->set('jsoneditor', $this);
        JS::set('jsoneditor.jsoneditor', [
            'json' => $this->getJSONData(),
            'settings' => $this->getSettings(),
        ]);
    }

    /**
     * Default value for the json editor.
     *
     * @return array
     */
    public function getJSONData() {
        return [];
    }

    /**
     * Default settings for the json editor.
     *
     * @return array
     */
    public function getSettings() {
        return [];
    }

    /**
     * Get the posted data as a JSON string.
     *
     * @return string
     */
    public function postedData() {
        return Request::post('jsoneditor', 'json_string');
    }
}
