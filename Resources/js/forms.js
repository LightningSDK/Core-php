(function(){
    var self = lightning.forms = {
        formSubmit: function(e) {
            // Determine the submit action.
            var form = $(e.target).closest('form');
            var url = form.data('ajax-action');
            if (!url) {
                url = form.prop('action');
            }

            // Make sure the form's captcha is validated if present
            if (!self.validateForm(e)) {
                return false;
            }

            // Show the loader
            var container = form.find('.loading-container');
            if (container.length === 0) {
                container = form.closest('.loading-container');
            }
            var existingVeil;
            if (container) {
                existingVeil = container.children('.white-veil');
                if (existingVeil.length === 0) {
                    container.prepend('<div class="white-veil"><div class="spinner"></div></div>');
                }
            }
            existingVeil = container.children('.white-veil');
            setTimeout(function(){
                existingVeil.show();
                existingVeil.css('opacity', .5);
            }, 100);

            // Set up the success callback
            var successCallback;
            var successCallbackName = form.data('ajax-success');
            if (successCallbackName && window[successCallbackName]) {
                successCallback = window[successCallbackName];
            } else {
                successCallback = function(data) {
                    container.fadeOut();
                };
            }

            // Send the request
            $.ajax({
                url: url,
                type: 'POST',
                data: form.serializeArray(),
                success: successCallback
            });
        },

        init: function() {
            $('.captcha_container').closest('form').submit(function(){
                var form = $(this);
                var valid = grecaptcha.getResponse().length !== 0;
                $('#captcha_abide').val(valid ? 1 : '');
                return form && valid;
            });

            $('form[data-abide="ajax"]').on('valid.fndtn.abide', self.formSubmit);
        },

        initInvisibleCaptcha: function() {
            if (typeof grecaptcha === 'undefined' || !grecaptcha.render) {
                setTimeout(self.initInvisibleCaptcha, 500);
                return;
            }
            var publicKey = lightning.get('invisibleRecaptcha.publicKey');
            $('.invisible-recaptcha').each(function() {
                var form = $(this).closest('form');
                var id = grecaptcha.render(this, {
                    sitekey : publicKey,
                    size: 'invisible',
                    callback : function(token) {
                        form.submit();
                    }
                });
                form.data('recaptcha-id', id);
            });
        },

        validateForm: function(e) {
            var form = $(e.target);

            if (form.find('.error:visible').length > 0) {
                return false;
            }

            var recaptcha = form.find('.g-recaptcha-response');
            if (recaptcha.length > 0 && form.find('.g-recaptcha-response').val() === '') {
                grecaptcha.execute(form.data('recaptcha-id'));
                return false;
            }

            return true;
        }
    };
})();
