<?php

namespace Lightning\View;

use Lightning\Tools\Request;

abstract class JSONEditor extends Page {
    public $page = 'json_editor';

    public function get() {
        CSS::add('/css/jsoneditor.min.css');
        JS::add('/js/jsoneditor.min.js', false);
        JS::set('jsoneditor.jsoneditor', [
            'json' => $this->getJSONData(),
            'settings' => $this->getSettings(),
        ]);
        JS::startup('lightning.jsoneditor.init()');
    }

    /**
     * Default value for the json editor.
     *
     * @return array
     */
    protected function getJSONData() {
        return [];
    }

    /**
     * Default settings for the json editor.
     *
     * @return array
     */
    protected function getSettings() {
        return [];
    }

    /**
     * Get the posted data as a JSON string.
     *
     * @return string
     */
    protected function postedData() {
        return Request::post('json', 'json_string');
    }
}
