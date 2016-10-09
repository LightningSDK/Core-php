lightning.admin = {};
/**
 * Functions for editing mailing messages.
 *
 * @type {{checkVars: Function}}
 */
lightning.admin.messageEditor = {
    checkVars: function() {
        var selected_items = $('#message_criteria_input_array').val();
        $.ajax({
            url:'/admin/mailing/messages',
            data:{
                action: 'fields',
                criteria_list: selected_items,
                message_id: $('#id').val()
            },
            dataType:'JSON',
            type:'GET',
            success:function(data) {
                selected_items = selected_items.split(',');
                // Iterate over active criteria.
                for(var i=0; i< selected_items.length; i++) {
                    if (selected_items[i] != '') {
                        // Iterate over returned criteria.
                        for (var j in data.criteria) {
                            var criteria_id = data.criteria[j].criteria_id;
                            // If this is the matching criteria id.
                            if (data.criteria[j].criteria_id == selected_items[i]) {
                                // Iterate over variables.
                                for (var k in data.criteria[j].variables) {
                                    // If the field is not already present.
                                    var variable = data.criteria[j].variables[k];
                                    if ($('#var_' + criteria_id + '_' + variable).length == 0) {
                                        var value = (data.criteria[j].values && data.criteria[j].values[variable]) ? data.criteria[j].values[variable] : '';
                                        $('#message_criteria_box_' + selected_items[i])
                                            .append('<div id="var_' + criteria_id + '_' + variable + '" >' + variable + ': <input type="text" name="var_' + criteria_id + '_' + variable + '" value="' + value + '"></div>');
                                    }
                                }
                            }
                        }
                    }
                }
            },
            error:function() {
                alert('error');
            }
        });
    }
};
lightning.admin.messages = {
    send: function(type, n) {
        // Start AJAX transmission.
        $('#message_status').html('Starting ...\n');
        var mail_buttons = $('.mail_buttons').fadeOut();
        var last_response_len = 0;
        var self = this;
        var data = {
            token: lightning.vars.token,
            action: 'send-' + type,
            id: lightning.vars.message_id
        };
        if (type == 'random') {
            data.count = n;
        }
        $.ajax({
            url: '/admin/mailing/send',
            dataType: 'text',
            data: data,
            type: 'POST',
            stream: true,
            xhrFields: {
                onprogress: function(e) {
                    var response = e.currentTarget.response;
                    self.addContent('#message_status', response.substring(last_response_len));
                    last_response_len = response.length;
                }
            },
            complete: function() {
                mail_buttons.fadeIn();
            },
            error: function() {
                mail_buttons.fadeIn();
            }
        });
    },
    addContent: function (container, content) {
        var $container = $(container);
        $container.html($container.html() + content);
        $container.animate({ scrollTop: $container.attr("scrollHeight") }, 500);
    }
};

(function(){
    var self = lightning.admin.css = {
        css_id: null,
        version: 0,
        init: function () {
            $('#css-preview').click(self.preview);
            $('#css-save').click(self.save);
        },
        update: function (url) {
            $('link#managed_css').prop('href', url);
        },
        preview: function () {
            if (self.css_id == undefined) {
                self.css_id = parseInt(Math.random() * 1000000);
            }
            $.ajax({
                url: '/admin/css',
                type: 'POST',
                data: {
                    action: 'preview',
                    scss: $('#scss_content').val(),
                    id: self.css_id,
                    token: lightning.vars.token,
                },
                success: function (data) {
                    window.opener.lightning.admin.css.update('/admin/css?id=' + self.css_id + '&v=' + (self.version++) + '&action=preview');
                }
            });
        },
        save: function() {
            $.ajax({
                url: '/admin/css',
                type: 'POST',
                data: {
                    action: 'save',
                    scss: $('#scss_content').val(),
                    token: lightning.vars.token,
                },
                success: function(data) {
                    window.opener.lightning.admin.css.update(data.url + '?pv=' + (self.version++));
                }
            });
        }
    };
})();
