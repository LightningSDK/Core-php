(function(){
    var self = lightning.js = {
        queue: [],
        loaded: {},
        loadScript: function(scriptURL) {
            self.loaded[scriptURL] = false;
            var script = document.createElement('script');
            script.src = scriptURL + '?v=' + lightning.get('minified_version');
            script.type = 'text/javascript';
            script.async = 'true';
            script.onload = script.onreadystatechange = function() {
                var rs = this.readyState;
                if (rs && rs != 'complete' && rs != 'loaded') return;
                self.loaded[scriptURL] = true;
                self.trigger();
            };
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(script, s);
        },

        /**
         * Execute a callback as soon as all required JS scripts are loaded.
         *
         * @param {array|string} urls
         * @param {function} callback
         */
        require: function(urls, callback) {
            if (typeof urls === "string") {
                urls = [urls];
            }

            self.queue.push({
                urls: urls,
                callback: callback,
                triggered: false
            });

            for (var i in urls) {
                if (!self.loaded.hasOwnProperty(urls[i])) {
                    // Any scripts that are not already included can be included here.
                    if (urls[i].match(/\.js.*/)) {
                        self.loadScript(urls[i]);
                    }
                }
            }

            self.trigger();
        },

        /**
         * Attempts to run any callbacks whose dependencies have loaded
         */
        trigger: function() {
            // Iterate over each queued item.
            queue:
                for (var i in self.queue) {
                    // See if all scripts are loaded.
                    if (!self.queue[i].triggered) {
                        for (var j in self.queue[i].urls) {
                            // Trigger the script.
                            var url = self.queue[i].urls[j];
                            if (
                                // this checkso for js files and "document"
                                (!self.loaded.hasOwnProperty(url) || !self.loaded[url])
                                // this works for 'lightning' or '$' jquery
                                && ("undefined" === typeof window[url] || "document" === url)
                            ) {
                                continue queue;
                            }
                        }
                        self.queue[i].triggered = true;
                        self.queue[i].callback();
                    }
                }
        },

        documentOnReady: function() {
            self.loaded['document'] = true;
            self.trigger();
        },

        /**
         * Any scripts that were requested to run before the require function was established
         * will be added to the require queue.
         */
        loadStartupQueue: function() {
            // When this happens lightnign.js is loaded
            self.loaded['/js/lightning.min.js'] = true;

            // Set up notification for when the document is ready
            window.onload = self.documentOnReady;
            if (document.readyState === 'complete') {
                self.documentOnReady();
            }

            for (var i in $lsq) {
                self.require($lsq[i].r, $lsq[i].c);
            }
        },

        addModule: function(name, module) {
            if("undefined" == typeof lightning.modules) {
                lightning.modules = {};
            }
            lightning.modules[name] = module;
        }
    };
})();
