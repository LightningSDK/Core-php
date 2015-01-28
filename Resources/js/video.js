lightning.video = {

    players: {},

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
                lightning.getMethodReference(video.call.beforeLoad)(i, function(){
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
        var video_tag = '<video id=video_player_' + id + ' class="video-js vjs-default-skin" width="640" height="360" poster="' + (video.still ? video.still : '') + '" ' + (showControls ? 'controls' : '') + ' preload>';
        for (var codec in {'mp4': 1, 'ogg': 1, 'webm': 1}) {
            if (video[codec]) {
                video_tag += '<source src="' + video[codec] + '" type="video/' + codec + ';">';
            }
        }
        // TODO: Add flash fallback here.
        video_tag += '</video>';
        container.append(video_tag);

        // Initialize the player.
        videojs.autoSetup();
        this.players[id] = videojs(
            document.getElementById('video_player_' + id),
            {
                width: '100%',
                controls: showControls
            }
        );

        if (video.classes) {
            $('#video_player_' + id).addClass(video.classes);
        }

        // Start playing.
        if (video.autoPlay) {
            this.players[id].play();
            // Jump to the start time.
            if (video.startTime && video.startTime > 0) {
                this.players[id].currentTime(video.startTime);
            }
            if (video.call.afterLoad) {
                this.players[id].one('play', video.call.afterLoad);
            }
        }

        if (video.call.onEnd) {
            this.players[id].on('ended', lightning.getMethodReference(video.call.onEnd));
        }

        if (video.call.onTime) {
            var self = this;
            this.players[id].on('timeupdate', function(){
                self.timeCallback(self.players[id], lightning.vars.videos[id].call.onTime);
            })
        }
    },

    timeCallback: function(video, events) {
        var time = video.currentTime()
        for (var triggerTime in events) {
            if (time >= triggerTime) {
                lightning.getMethodReference(events[triggerTime])();
                delete events[triggerTime];
            }
        }
    }
};
