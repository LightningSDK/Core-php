(function(){
    var self = lightning.forms = {
        formSubmit: function(e) {
            var form = $(e.target).closest('form');
            var url = form.data('ajax-action');
            if (!url) {
                url = form.prop('action');
            }
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

            $.ajax({
                url: url,
                type: 'POST',
                data: form.serializeArray(),
                success: function(data){
                    // Hide the container
                    container.fadeOut();
                }
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
            if (typeof grecaptcha === 'undefined') {
                setTimeout(self.initInvisibleCaptcha, 500);
                return;
            }
            $('.invisible-recaptcha').each(function() {
                var form = $(this).closest('form');
                grecaptcha.render(this, {
                    'sitekey' : lightning.get('invisibleRecaptcha.publicKey'),
                    'callback' : function(token){
                        form.submit();
                    }
                });
            });
        }
    };
})();
