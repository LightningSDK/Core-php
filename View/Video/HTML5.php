<?php

namespace lightningsdk\core\View\Video;

use lightningsdk\core\View\CSS;
use lightningsdk\core\View\JS;

class HTML5 {
    /**
     * Initialize requires CSS and JS files.
     */
    public static function initDisplay() {
        static $inited = false;
        if (!$inited) {
            CSS::add('/js/videojs/video-js.min.css');
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
        JS::startup('lightning.video.init()', ['/js/videojs/video.min.js']);
    }

    /**
     * Render the default video container.
     *
     * @param $video_id
     * @param array $settings
     * @return string
     */
    public static function render($video_id, $settings = []) {
        return '<div id="video_' . $video_id . '" class="' . (!empty($settings['widescreen']) ? 'widescreen' : '') . '"></div>';
    }
}
