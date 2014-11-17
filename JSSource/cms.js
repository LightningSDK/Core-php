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
    },

    initImage: function() {
        $('.imagesCSS').keyup(function() {
            var textField = $(this);
            var id = textField.attr('id').replace('_class', '');
            var classes = textField.attr('name') + ' ' + textField.val();
            $('#' + id).removeClass().addClass(classes);
        });
    },

    editImage: function(id) {
        var self = this;
        CKFinder.popup({
            basePath: lightning.cms.basepath,
            selectActionFunction: function(fileUrl) {
                self.updateImage(id, fileUrl);
            }
        });
    },

    updateImage: function(id, fileUrl) {
        $('#cms_' + id).attr('src', fileUrl);
    },

    saveImage: function(id) {
        $.ajax({
            url: '/admin/cms',
            type: 'POST',
            dataType: 'json',
            data: {
                cms: id,
                class: $('#cms_' + id + '_class').val(),
                token: lightning.vars.token,
                action: "save-image",
                content: $('#cms_' + id).attr('src')
            },
            success:function(data){
                if(data.status != 'success'){
                    var error = '';
                    for (var i in data.errors) {
                        error += data.error[i] + ' ';
                    }
                    if (error == '') {
                        error = 'Could not save: Unknown error.';
                    }
                }
            },
            error:function(){
                alert('The image could not be saved, please try again later.');
                self.edit();
            }
        });
    },

    editPlain: function(id) {
        $('#display_cms_' + id).hide();
        $('#cms_' + id).show();
    },

    savePlain: function(id) {
        $.ajax({
            url: '/admin/cms',
            type: 'POST',
            dataType: 'json',
            data: {
                cms: id,
                token: lightning.vars.token,
                action: "save",
                content: $('#cms_' + id).val()
            },
            success:function(data){
                if(data.status != 'success'){
                    var error = '';
                    for (var i in data.errors) {
                        error += data.error[i] + ' ';
                    }
                    if (error == '') {
                        error = 'Could not save: Unknown error.';
                    }
                } else {
                    // Switch back to the main view.
                    $('#cms_' + id).hide();
                    $('#display_cms_' + id).html($('#cms_' + id).val()).show();
                }
            },
            error:function(){
                alert('The image could not be saved, please try again later.');
                self.edit();
            }
        });
    }
};
