lightning.format = {
    sizes: ['B', 'KB', 'MB', 'GB', 'TB', 'PB'],
    counts: ['', 'k', 'm', 'b', 't', 'q'],
    dataSize: function(bytes) {
        return lightning.format.divide(bytes, 1024, lightning.format.sizes, false);
    },
    count: function(number) {
        return lightning.format.divide(number, 1000, lightning.format.counts, true);
    },
    divide: function(number, divide, suffix, int) {
        var output = number;
        var size = 0;
        while (output >= divide * .9) {
            output /= divide;
            size++;
        }
        if (suffix[size] == undefined) {
            return 'Really Big';
        } else {
            if (size == 0 && int) {
                return output;
            } else {
                return parseFloat(output).toPrecision(3) + suffix[size];
            }
        }
    }
};

/**
 * The contains startup functions that can run on each page.
 */
lightning.startup = {
    init: function() {
        this.initNav();
        lightning.ajax.init();
        lightning.dialog.init();
        lightning.tracker.init();
        lightning.forms.init();
    },

    initNav: function() {
        var menu_context = lightning.get('menu_context');
        if (menu_context) {
            $('nav .' + menu_context).addClass('active');
        }
    }
};
lightning.js = {
    queue: [],
    loaded: {},
    loadScript: function(scriptURL) {
        lightning.js.loaded[scriptURL] = false;
        var script = document.createElement('script');
        script.src = scriptURL;
        script.type = 'text/javascript';
        script.async = 'true';
        script.onload = script.onreadystatechange = function() {
            var rs = this.readyState;
            if (rs && rs != 'complete' && rs != 'loaded') return;
            lightning.js.loaded[scriptURL] = true;
            lightning.js.trigger();
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

        if (lightning.js.mv > 0) {
            for (var i in urls) {
                urls[i] = urls[i] + '?v=' + lightning.js.mv;
            }
        }

        lightning.js.queue.push({
            urls: urls,
            callback: callback,
            triggered: false
        });

        for (var i in urls) {
            if (!lightning.js.loaded.hasOwnProperty(urls[i])) {
                // Any scripts that are not already included can be included here.
                if (urls[i].match(/\.js\?v=[0-9]+/)) {
                    lightning.js.loadScript(urls[i]);
                }
            }
        }

        lightning.js.trigger();
    },

    /**
     *
     */
    trigger: function() {
        // Iterate over each queued item.
        lightning.js.loaded['/js/lightning.min.js?v='+lightning.js.mv] = true;
        queue:
        for (var i in lightning.js.queue) {
            // See if all scripts are loaded.
            if (!lightning.js.queue[i].triggered) {
                for (var j in lightning.js.queue[i].urls) {
                    // Trigger the script.
                    var url = lightning.js.queue[i].urls[j];
                    if (url.match(/\.js\?v=[0-9]+/) && !lightning.js.loaded[url]) {
                        continue queue;
                    } else if ("undefined" != typeof window[url]) {
                        continue queue;
                    }
                }
                lightning.js.queue[i].triggered = true;
                lightning.js.queue[i].callback();
            }
        }
    },

    /**
     * Any scripts that were requested to run before the require function was established
     * will be added to the require queue.
     */
    loadStartupQueue: function() {
        lightning.js.mv = lightning.get('minified_version');
        for (var i in $lsq) {
            lightning.js.require($lsq[i].r, $lsq[i].c);
        }
    },

    addModule: function(name, module) {
        if("undefined" == typeof lightning.modules) {
            lightning.modules = {};
        }
        lightning.modules[name] = module;
    }
};

/**
 * Get a deep lightning variable using . notation.
 *
 * @param {string} locator
 * @param {mixed} defaultValue
 *
 * @returns {*}
 */
lightning.get = function(locator, defaultValue) {
    if (!locator) {
        return null;
    }
    if (typeof defaultValue == 'undefined') {
        defaultValue = null;
    }
    locator = locator.split('.');
    var value = lightning.vars;
    for (var i in locator) {
        if (value.hasOwnProperty(locator[i])) {
            value = value[locator[i]];
        } else {
            return defaultValue;
        }
    }
    return value;
};

/**
 * Load from hash or query string.
 */
lightning.parsed_query = null;
lightning.parsed_hash = null;
lightning.getFromURL = function(field, q) {
    var parsedField = 'parsed_' + field;
    // If the query has not been parsed yet.
    if (lightning[parsedField] == null) {
        // Parse the query.
        var query = document.location[field].substring(1).split('&');
        lightning[parsedField] = {};
        for (var i in query) {
            if (query[i].length == 0) {
                continue;
            }
            var split = query[i].split('=', 2);
            lightning[parsedField][split[0]] = split[1];
        }
    }

    // If there is a value for the requested property, return it.
    if (lightning[parsedField].hasOwnProperty(q)) {
        return lightning[parsedField][q];
    }
    return null;
};
lightning.query = function(q) {
    return lightning.getFromURL('search', q);
};
lightning.hash = function(q) {
    return lightning.getFromURL('hash', q);
};
if (!lightning.modules) {
    lightning.modules = {};
}
lightning.buildQuery = function(parameters) {
    var pairs = [];
    for (var i in parameters) {
        pairs.push(encodeURIComponent(i) + '=' + encodeURIComponent(parameters[i]));
    }
    return pairs.join('&');
};
