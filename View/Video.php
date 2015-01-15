<?php

namespace Lightning\View;

class Video {

    public static function initDisplay() {
        $html5Video = self::html5Video();
        JS::set('video.html5', $html5Video);
    }

    public static function html5Video() {
        return (( strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'chrome')) && !strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android'));
    }

    public static function add($video_id, $settings) {
        JS::set('lightning.video.' . $video_id, $settings);
        JS::startup('lightning.video.init()');
    }
}
