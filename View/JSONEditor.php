<?php

namespace Lightning\View;

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

    protected function getJSONData() {
        return [];
    }

    protected function getSettings() {
        return [];
    }
}
