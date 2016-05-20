lightning.format = {
    sizes: ['B', 'KB', 'MB', 'GB', 'TB', 'PB'],
    dataSize: function(bytes) {
        var output = bytes;
        var size = 0;
        while (output >= 1000) {
            output /= 1024;
            size++;
        }
        if (this.sizes[0] == undefined) {
            return 'Really Big';
        } else {
            return output.toPrecision(3) + this.sizes[size];
        }
    }
};

lightning.startup = {
    init: function() {
        this.initForms();
        this.initNav();
        lightning.ajax.init();
        lightning.dialog.init();
        lightning.splitTest.init();
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

lightning.get = function(locator) {
    if (!locator) {
        return null;
    }
    locator = locator.split('.');
    var value = lightning.vars;
    for (var i in locator) {
        if (value.hasOwnProperty(locator[i])) {
            value = value[locator[i]];
        } else {
            return null;
        }
    }
    return value;
};
