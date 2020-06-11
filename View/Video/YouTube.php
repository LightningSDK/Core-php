<?php

namespace lightningsdk\core\View\Video;

use lightningsdk\core\View\JS;

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
    public static function render($video_id, $settings = []) {
        JS::startup('lightning.video.initYouTube()');
        $autoplay = !empty($settings['autoplay']) ? 'data-autoplay="true"' : '';
        return '<div class="youtube" id="' . $video_id . '" ' . $autoplay . '></div>';
    }

    public static function renderMarkup($options, $vars) {
        $output = YouTube::render($options['id'], [
            'autoplay' => !empty($options['autoplay']) ? true : false,
        ]);
        if (!empty($options['flex'])) {
            $output = '<div class="flex-video ' . (!empty($options['widescreen']) ? 'widescreen' : '') . '">' . $output . '</div>';
        }
        return $output;
    }
}
