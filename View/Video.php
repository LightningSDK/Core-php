<?php

namespace Lightning\View;

class Video {

    public static function initDisplay() {
        static $inited = false;
        if (!$inited) {
            $html5Video = self::html5Video();
            JS::set('video.html5', $html5Video);
            JS::add('/js/video-js.min.js', $html5Video);
            JS::startup('videojs.options.flash.swf = "/swf/video-js.swf"');
            CSS::add('/css/video-js.min.css', $html5Video);
            $inited = true;
        }
    }

    public static function html5Video() {
        return (( strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'chrome')) && !strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'android'));
    }

    public static function add($video_id, $settings) {
        self::initDisplay();
        JS::set('videos.' . $video_id, $settings);
        JS::startup('lightning.video.init()');
    }
}
