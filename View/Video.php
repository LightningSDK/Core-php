<?php

namespace Lightning\View;

class Video {

    public static function initDisplay() {
        static $inited = false;
        if (!$inited) {
            JS::add('/js/video-js.min.js');
            JS::startup('videojs.options.flash.swf = "/swf/video-js.swf"');
            CSS::add('/css/video-js.min.css');
            $inited = true;
        }
    }

    public static function add($video_id, $settings) {
        self::initDisplay();
        JS::set('videos.' . $video_id, $settings);
        JS::startup('lightning.video.init()');
    }
}
