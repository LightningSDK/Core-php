lightning.page = {
    edit: function() {
        $('.page_edit').show();
        $('.page_edit_links').hide();
        $('#page_display').attr('contentEditable', 'true');
        lightning.ckeditors['page_editor'] = CKEDITOR.inline("page_display", {
                toolbar:CKEDITOR.config.toolbar_Full
            }
        );
        lightning.ckeditors['page_editor'].setData(lightning.vars.page.source);
        CKFinder.setupCKEditor(lightning.ckeditors['page_editor'], '/js/ckfinder/');
    },

    save: function() {
        $('#save_button').hide();
        lightning.ckeditors['page_editor'].destroy();
        delete lightning.ckeditors['page_editor'];
        var send = {
            page_id: $('#page_id').val(),
            token: $('#token').val(),
            action: "save",
            title: $("#page_title").val(),
            url: $("#page_url").val(),
            description: $("#page_description").val(),
            keywords: $('#page_keywords').val(),
            sitemap: $('#page_sitemap').is(":checked")?1:0,
            layout: $('#page_layout').val(),
            page_body: $('#page_display').html()
        };
        var self = this;
        $.ajax({
            url:'/page',
            type:'POST',
            dataType:'json',
            data:send,
            success:function(data) {
                if (data.status == 'success') {
                    // Hide the editing controls.
                    $('.page_edit').hide();
                    $('.page_edit_links').show();
                    $('#page_display').attr('contentEditable', 'false');
                    // Update page specific data.
                    $('#page_id').val(data.page_id);
                    $('#page_url').val(data.url);
                    document.title = data.title;
                    $('#page_title').val(data.title);
                } else {
                    // Saving failed.
                    alert(data.error);
                    self.edit();
                }
            },
            error:function() {
                alert('The page could not be saved, please try again later.');
                self.edit();
            }
        });
    }
};
