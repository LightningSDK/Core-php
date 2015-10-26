<?php

namespace Lightning\View\Video;

use Lightning\View\CSS;
use Lightning\View\JS;

class HTML5 {
    /**
     * Initialize requires CSS and JS files.
     */
    public static function initDisplay() {
        static $inited = false;
        if (!$inited) {
            JS::add('/js/video-js.min.js', false);
            JS::startup('videojs.options.flash.swf = "/swf/video-js.swf"');
            CSS::add('/css/video-js.min.css');
            $inited = true;
        }
    }

    /**
     * Add a video's JS and CSS components.
     * This does not create the video's required HTML components.
     *
     * @param string $video_id
     *   The ID for the video.
     * @param array $settings
     *   The settings for the video.
     */
    public static function add($video_id, $settings) {
        self::initDisplay();
        JS::set('videos.' . $video_id, $settings);
        JS::startup('lightning.video.init()');
    }
}
