lightning.video = {
    /**
     * Prepare the video.
     */
    init: function() {
        var self = this;
        for (var i in lightning.vars.videos) {
            var video = lightning.vars.videos[i];
            if (!video.call) {
                video.call = [];
            }
            if (video.call.beforeLoad) {
                window[video.call.beforeLoad](i, function(){
                    self.load(i);
                });
            } else {
                this.load(i);
            }
        }
    },

    /**
     * Build the video HTML.
     */
    load: function(id) {
        var video = lightning.vars.videos[id];
        var container = $('#video_' + id);
        var showControls = !video.hasOwnProperty('controls') || video.controls;
        if (true) {
            var video_tag = '<video id=video_player_' + id + ' class="video-js vjs-default-skin" width="640" height="360" poster="' + (video.still ? video.still : '') + '" ' + (showControls ? 'controls' : '') + ' preload>';
            for (codec in {'mp4': 1, 'ogg': 1, 'webm': 1}) {
                if (video[codec]) {
                    video_tag += '<source src="' + video[codec] + '" type="video/' + codec + ';">';
                }
            }
            // TODO: Add flash fallback here.
            video_tag += '</video>';
            container.append(video_tag);

            // Initialize the player.
            videojs.autoSetup();
            var myPlayer = videojs(
                document.getElementById('video_player_' + id),
                {
                    width: '100%',
                    controls: showControls,
                }
            );

            // Start playing.
            if (video.autoPlay) {
                myPlayer.play();
                // Jump to the start time.
                if (video.startTime && video.startTime > 0) {
                    myPlayer.currentTime(video.startTime);
                }
                myPlayer.one("play", video.call.afterLoad);
            }
        }
    }
};
