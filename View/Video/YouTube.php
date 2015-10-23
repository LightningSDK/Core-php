<?php

namespace Lightning\View\Video;

use Lightning\View\JS;

class YouTube {
    /**
     * Call this before the template loads to make sure the JS is initted as soon as possible.
     */
    public static function init() {
        JS::startup('lightning.video.initYouTube()');
    }

    /**
     * Add a video's JS and CSS components.
     * This does not create the video's required HTML components.
     *
     * @param string $video_id
     *   The ID for the video.
     *
     * @return string
     *   The rendered HTML.
     */
    public static function render($video_id) {
        JS::startup('lightning.video.initYouTube()');
        return '<div class="youtube" id="' . $video_id . '" ></div>';
    }
}
