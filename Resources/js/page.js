(function(){
    var self;
    lightning.page = {
        edit: function() {
            $('.page_edit').show();
            $('.page_edit_links').hide();
            // Move the unrendered html into the div.
            $('#page_display').html($('#save_page_display').val());
            // Activate the editor.
            lightning.htmleditor.initEditor('page_display');
        },

        save: function() {
            $('#save_button').hide();
            lightning.dialog.showLoader('Saving...');
            var data = {
                page_id: $('#page_id').val(),
                token: $('#token').val(),
                action: "save",
                title: $("#page_title").val(),
                url: $("#page_url").val(),
                menu_context: $("#page_menu_context").val(),
                description: $("#page_description").val(),
                keywords: $('#page_keywords').val(),
                sitemap: $('#page_sitemap').is(":checked") ? 1 : 0,
                layout: $('#page_layout').val(),
                page_body: lightning.htmleditor.getContent('page_display'),
            };
            $.ajax({
                url:'/page',
                type:'POST',
                dataType:'json',
                data: data,
                success:function(data) {
                    // Hide the editing controls.
                    lightning.htmleditor.deactivateEditor('page_display');
                    $('.page_edit').hide();
                    $('.page_edit_links').show();
                    $('#page_display').attr('contentEditable', 'false');
                    // Update page specific data.
                    $('#page_id').val(data.page_id);
                    $('#page_url').val(data.url);
                    document.title = data.title;
                    $('#page_header').html(data.title);
                    // Replace the unrendered html with the new html
                    $('#save_page_display').val(data.page_body);
                    $('#page_display').html(data.body_rendered);
                    lightning.dialog.clear();
                    lightning.dialog.add('Saved!', 'message');
                },
                error:function() {
                    lightning.dialog.clear();
                    lightning.dialog.add('The page could not be saved, please try again!', 'error');
                    self.edit();
                }
            });
        }
    };
    self = lightning.page;
})();
