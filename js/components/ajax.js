(function(){
    var self = lightning.ajax = {
        /**
         * This will hold the original jQuery ajax.
         */
        jqueryAjax: function () {},

        /**
         * Called at startup to replace the original jQuery function with the lightning version.
         */
        init: function () {
            // Save the original ajax function.
            self.jqueryAjax = jQuery.ajax;

            // Override the jquery ajax function.
            jQuery.ajax = self.call;
        },

        /**
         * The lightning callback that will wrap the user callback.
         *
         * @param {object} settings
         *   The ajax connection settings.
         * @param {string|object} data
         *   The data returned by the server.
         */
        success: function (settings, data) {
            // If the output was HTML, add it to the dialog.
            if (settings.dataType === 'HTML') {
                if (settings.user_success) {
                    settings.user_success(data);
                } else {
                    lightning.dialog.showContent(data);
                }
                return;
            }

            if (!settings.hasOwnProperty('persist_dialog') || settings.persist_dialog === false) {
                lightning.dialog.clear();
            }

            // Add standard messages to the dialog.
            if (data && data.messages && data.messages.length > 0) {
                for (var i in data.messages) {
                    lightning.dialog.add(data.messages[i], 'message');
                }
                lightning.dialog.show();
            }

            // Add standard error messages.
            if (data && data.errors && data.errors.length > 0) {
                for (var i in data.errors) {
                    lightning.dialog.add(data.errors[i], 'error');
                }
                lightning.dialog.show();
                return;
            }

            // If there is additional content to show.
            if (data.content) {
                lightning.dialog.showContent(data.content);
            }

            // Are there variables to set?
            if (data.js_vars) {
                $.extend(lightning.vars, data.js_vars);
            }

            // Is there JS to run?
            if (data.js_startup) {
                for (var i in data.js_startup) {
                    if (data.js_startup[i].requires && data.js_startup[i].requires.length > 0) {
                        lightning.js.require(data.js_startup[i].requires, function () {
                            eval(data.js_startup[i].script);
                        });
                    } else {
                        eval(data.js_startup[i].script);
                    }
                }
            }

            // Process redirect.
            if (data && data.status && data.status == 'redirect') {
                document.location = data.location;
                return;
            }

            // No errors handling, just call the success function if there is one.
            if (settings.user_success) {
                settings.user_success(data);
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
        error: function (settings, data) {
            lightning.dialog.clear();
            if (data === undefined) {
                lightning.dialog.add('Communication Error', 'error');
                lightning.dialog.show();
            } else if (typeof(data) === 'string') {
                lightning.dialog.add(data, 'error');
                lightning.dialog.show();
            } else {
                if (data.hasOwnProperty('responseJSON')) {
                    var json = data.responseJSON;
                    if (json.hasOwnProperty('errors')) {
                        for (var i in json.errors) {
                            lightning.dialog.add(json.errors[i], 'error');
                        }
                    } else {
                        lightning.dialog.add('An unknown error has occurred.', 'error');
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
                lightning.dialog.show();
            }

            // Allows an additional error handler.
            if (settings.user_error) {
                settings.user_error(data);
            }
        },

        /**
         * This will replace the jQuery.ajax method, and will wrap the user success and error
         * callbacks with the lightning standard callbacks.
         *
         * @param {object} settings
         *   The settings intended for the jQuery.ajax call.
         */
        call: function (settings) {
            settings.user_error = settings.error;
            settings.user_success = settings.success;

            // Override the success handler.
            settings.success = function (data) {
                self.success(settings, data);
            };
            // Override the error handler.
            settings.error = function (data) {
                // TODO: make this more graceful.
                self.error(settings, data);
            };
            // Call the original ajax function.
            self.jqueryAjax(settings);
        }
    };
})();
