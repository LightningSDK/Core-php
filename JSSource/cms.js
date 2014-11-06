lightning.cms = {
    edit: function(editor) {
        $('#' + editor).attr('contentEditable', 'true');
        lightning.ckeditors[editor] = CKEDITOR.inline(editor, {
                toolbar: CKEDITOR.config.toolbar_Full,
                allowedContent: true
            }
        );
        $('#' + editor.replace(/^cms_/, 'cms_edit_')).hide();
        $('#' + editor.replace(/^cms_/, 'cms_save_')).removeClass('hide').show();
    },

    save: function (editor) {
        lightning.ckeditors[editor].destroy();
        var self = this;
        $.ajax({
            url: '/admin/cms',
            type: 'POST',
            dataType: 'json',
            data: {
                cms: editor.replace(/^cms_/, ''),
                token: lightning.vars.token,
                action: "save",
                content: $('#' + editor).html()
            },
            success:function(data){
                if(data.status == 'success'){
                    $('#' + editor.replace(/^cms_/, 'cms_edit_')).show();
                    $('#' + editor.replace(/^cms_/, 'cms_save_')).hide();
                } else {
                    var error = '';
                    for (var i in data.errors) {
                        error += data.error[i] + ' ';
                    }
                    if (error == '') {
                        error = 'Could not save: Unknown error.';
                    }
                    alert(error);
                    self.edit();
                }
            },
            error:function(){
                alert('The page could not be saved, please try again later.');
                self.edit();
            }
        });
    }
};
