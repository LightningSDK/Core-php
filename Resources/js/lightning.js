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
        this.initForms();
        this.initNav();
        lightning.ajax.init();
        lightning.dialog.init();
        lightning.tracker.init();
    },

    initNav: function() {
        var menu_context = lightning.get('menu_context');
        if (menu_context) {
            $('nav .' + menu_context).addClass('active');
        }
    },

    initForms: function() {
        $('.captcha_container').closest('form').submit(function(){
            var self = $(this);
            return self && (function(){
                    var valid = grecaptcha.getResponse().length != 0;
                    $('#captcha_abide').val(valid ? 1 : '');
                    return valid;
                })();
        });
    }
};

/**
 * Force external scripts to load asynchronously before executing a callback.
 *
 * @param {string|array} url
 *   A URL or array of urls of JS files.
 * @param callback
 *   A method to call when all the JS files have loaded.
 */
lightning.require = function(url, callback) {
    if (typeof url == "string") {
        url = [url];
    }
    var script_count = url.length;
    var scripts_loaded = 0;
    for (var i in url) {
        var script = document.createElement('script');
        script.src = url[i];
        script.type = 'text/javascript';
        script.async = 'true';
        script.onload = script.onreadystatechange = function() {
            var rs = this.readyState;
            if (rs && rs != 'complete' && rs != 'loaded') return;
            scripts_loaded ++;
            if (scripts_loaded == script_count) {
                try { callback() } catch (e) {
                    console.error(e);
                }
            }
        };
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(script, s);
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
lightning.modules = {};
lightning.buildQuery = function(parameters) {
    var pairs = [];
    for (var i in parameters) {
        pairs.push(encodeURIComponent(i) + '=' + encodeURIComponent(parameters[i]));
    }
    return pairs.join('&');
};
