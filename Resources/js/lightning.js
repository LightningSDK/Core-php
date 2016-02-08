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
    },

    initNav: function() {
        if (lightning.vars.active_nav && lightning.vars.active_nav.length > 0) {
            $('nav .' + lightning.vars.active_nav).addClass('active');
        }
    },

    initForms: function() {
        var forms = $('form.validate');

        if (forms.length == 0) {
            return;
        }

        // Add lightning validation rules.
        $.validator.addMethod("passwordLength", $.validator.methods.minlength, "Your password must be at least {0} characters long.");
        $.validator.addMethod("passwordVerify", function(value, element, param) {
            // Copied from $.validator.methods.equalTo
            var $element = $(element);
            var target = $element.closest('form').find(param);
            if ( this.settings.onfocusout ) {
                target.unbind( ".validate-equalTo" ).bind( "blur.validate-equalTo", function() {
                    $element.valid();
                });
            }
            return value === target.val();
        }, "Please enter the same password twice.");
        $.validator.addClassRules("password", { required: true, passwordLength: 6});
        $.validator.addClassRules("password2", { required: true, passwordLength: 6, passwordVerify: '.password'});

        // Activate validation for forms.
        forms.each(function() {
            var id = $(this).attr('id');
            if (lightning.formValidation && lightning.formValidation[id]) {
                $(this).validate(lightning.formValidation[id]);
            } else {
                $(this).validate();
            }
        });
    }
};
