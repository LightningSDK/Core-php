<?php

namespace lightningsdk\core\View;

class JSONEditor {
    public static function render($id, $settings = [], $data = []) {
        CSS::add('/css/jsoneditor.min.css');
        JS::add('/js/jsoneditor.min.js', false);
        JS::startup('lightning.jsoneditor.init()');
        if (empty($data)) {
            $data = [];
        } else if (is_string($data)) {
            $data = json_decode($data);
        }
        JS::set('jsoneditor.' . $id, [
            'json' => $data,
            'settings' => $settings,
        ]);
        $width = !empty($settings['width']) ? $settings['width'] : '100%';
        $height = !empty($settings['height']) ? $settings['height'] : '400px';
        return '<input name="' . $id . '" id="' . $id . '_data" type="hidden" class="jsoneditor_presave" /><div id="' . $id . '" style="width: ' . $width . '; height: ' . $height . ';"></div>';
    }
}
