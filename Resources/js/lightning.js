if (!lightning) {
    var lightning = {};
}

lightning.startup = {
    init: function() {
        this.initForms();
        this.initNav();
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
        $.validator.addMethod("passwordVerify", function(value, element, param){
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


function json_to_ul (jsondata, target) {
    if (typeof(jsondata) == 'undefined' || typeof(target) == 'undefined') {
        return false;
    }

    var items = [];

    $.each(jsondata, function(key, val) {
        items.push('<li class="blogpost"><a href="' + val.url + '">' + val.title + '</a></li>');
    });

    $('<ul/>', {
        'class': 'blogpostlist',
        html: items.join('')
    }).appendTo(target);

}

function json_on_id (jsonreq, target) {
    $.ajax({
        type: "GET",
        url: jsonreq,
        data: "json=1",
        dataType: "jsonp",
        jsonpCallback: target,
        cache: true,
        success: function (data) {
            json_to_ul(data, "#"+target);
        }
    });
}

function reset_field_value(field){
    // check for ckeditor
    if(typeof CKEDITOR.instances[field] !== "undefined")
        CKEDITOR.instances[field].setData(table_data.defaults[field]);

    // other fields
    else if(typeof ("#"+field).val !== "undefined")
        $('#'+field).val(table_data.defaults[field]);
    else
        $('#'+field).html(table_data.defaults[field]);

}
