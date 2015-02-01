lightning.ajax = {
    jqueryAjax: function(){},

    init: function() {
        // Save the original ajax function.
        this.jqueryAjax = jQuery.ajax;

        // Override the jquery ajax function.
        jQuery.ajax = this.call;
    },

    success: function(data, success_callback, error_callback) {
        if(settings.dataType == "HTML") {
            if(success_callback){
                success_callback(data);
            } else {
                lightning.dialog.dialog_set_content(data);
            }
        }
        else if(data.status == 'success'){
            if(success_callback){
                success_callback(data);
            } else {
                lightning.dialog.hide_dialog();
            }
        } else if(data.status == 'redirect') {
            // TODO: check for redirect cookie
            document.location = data.location;
        } else {
            // TODO: make this more graceful.
            lightning.ajax.error(data);
            if(error_callback) {
                error_callback(data);
            }
        }
    },

    error: function(data) {
        lightning.dialog.showPrepared(function(){
            if(typeof(data)=='string'){
                lightning.dialog.addError(data);
            } else if(data.hasOwnProperty('errors')){
                for(var i in data.errors) {
                    lightning.dialog.addError(data.errors[i]);
                }
            } else {
                lightning.dialog.addError('There was an error loading the page. Please reload the page. If the problem persists, please <a href="/contact">contact support</a>.');
                if(data.hasOwnProperty('status')) {
                    lightning.dialog.addError('HTTP: ' + data.status);
                }
                if(data.hasOwnProperty('responseText') && !data.responseText.match(/<html/i)) {
                    lightning.dialog.addError(data.responseText);
                }
            }
        });
    },

    call: function(settings) {
        var self = this;
        // Override the success handler.
        settings.success = function(data){
            self.success(data, settings.success, settings.error);
        };
        // Override the error handler.
        settings.error = function (data){
            // TODO: make this more graceful.
            self.error(data);
            // Allows an additional error handler.
            if(settings.error) {
                settings.error(data);
            }
        };
        // Call the original ajax function.
        this.jqueryAjax(settings);
    }
};
