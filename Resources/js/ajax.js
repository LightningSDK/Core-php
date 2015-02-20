lightning.ajax = {
    /**
     * This will hold the original jQuery ajax.
     */
    jqueryAjax: function() {},

    /**
     * Called at startup to replace the original jQuery function with the lightning version.
     */
    init: function() {
        // Save the original ajax function.
        this.jqueryAjax = jQuery.ajax;

        // Override the jquery ajax function.
        jQuery.ajax = this.call;
    },

    /**
     * The lightning callback that will wrap the user callback.
     *
     * @param {object} settings
     *   The ajax connection settings.
     * @param {string|object} data
     *   The data returned by the server.
     */
    success: function(settings, data) {
        // If the output was HTML, add it to the dialog.
        if (settings.dataType == "HTML") {
            if (settings.user_success) {
                settings.user_success(data);
            } else {
                lightning.dialog.setContent(data);
            }
        }
        // Add standard messages to the dialog.
        if (data.messages) {
            for(var i in data.messages) {
                lightning.dialog.add(data.messages[i], 'message');
            }
        }
        // Add standard error messages.
        if (data.errors && data.errors.length) {
            // TODO: make this more graceful.
            lightning.ajax.error(settings, data);
        }

        // Process success handling.
        if (data.status == 'success') {
            if (settings.user_success) {
                settings.user_success(data);
            } else {
                lightning.dialog.hide();
            }
        } else if (data.status == 'redirect') {
            // TODO: check for redirect cookie
            document.location = data.location;
        }
    },

    /**
     * The lightning ajax error handler.
     *
     * @param {object} settings
     *   The ajax connection settings.
     * @param {string|object} data
     *   The response from the server.
     */
    error: function(settings, data) {
        lightning.dialog.showPrepared(function() {
            if (data == undefined) {
                lightning.dialog.add('Communication Error', 'error');
            } else if (typeof(data) == 'string') {
                lightning.dialog.add(data, 'error');
            } else if (data.errors) {
                for(var i in data.errors) {
                    lightning.dialog.add(data.errors[i], 'error');
                }
            } else {
                lightning.dialog.add('There was an error loading the page. Please reload the page. If the problem persists, please <a href="/contact">contact support</a>.', 'error');
                if (data.hasOwnProperty('status')) {
                    lightning.dialog.add('HTTP: ' + data.status, 'error');
                }
                if (data.hasOwnProperty('responseText') && !data.responseText.match(/<html/i)) {
                    lightning.dialog.add(data.responseText, 'error');
                }
            }
            // Allows an additional error handler.
            if (settings.user_error) {
                settings.user_error(data);
            }
        });
    },

    /**
     * This will replace the jQuery.ajax method, and will wrap the user success and error
     * callbacks with the lightning standard callbacks.
     *
     * @param {object} settings
     *   The settings intended for the jQuery.ajax call.
     */
    call: function(settings) {
        settings.user_error = settings.error;
        settings.user_success = settings.success;

        // Override the success handler.
        settings.success = function(data) {
            lightning.ajax.success(settings, data);
        };
        // Override the error handler.
        settings.error = function (data) {
            // TODO: make this more graceful.
            lightning.ajax.error(settings, data);
        };
        // Call the original ajax function.
        lightning.ajax.jqueryAjax(settings);
    }
};
