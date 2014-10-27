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
        $('form.validate').each(function() {
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
