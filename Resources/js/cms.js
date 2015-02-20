lightning.cms = {
    edit: function(editor) {
        $('#' + editor).attr('contentEditable', 'true');
        lightning.ckeditors[editor] = CKEDITOR.inline(editor, {
                toolbar: CKEDITOR.config.toolbar_Full,
                allowedContent: true
            }
        );
        CKFinder.setupCKEditor(lightning.ckeditors[editor], '/js/ckfinder/');
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
            success:function(data) {
                if (data.status == 'success') {
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
            error:function() {
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
            success:function(data) {
                if (data.status != 'success') {
                    var error = '';
                    for (var i in data.errors) {
                        error += data.error[i] + ' ';
                    }
                    if (error == '') {
                        error = 'Could not save: Unknown error.';
                    }
                }
            },
            error:function() {
                alert('The image could not be saved, please try again later.');
            }
        });
    },

    initPlain: function() {
        var self = this;
        $('.cms_edit_plain').click(function(e) {
            var id = $(e.target).attr('id').replace(/^cms_edit_/, '');
            self.editPlain(id);
        });
        $('.cms_save_plain').click(function(e) {
            var id = $(e.target).attr('id').replace(/^cms_save_/, '');
            self.savePlain(id);
        });
    },

    editPlain: function(id) {
        $('#cms_edit_' + id).hide();
        $('#cms_save_' + id).show();
        $('#cms_display_' + id).hide();
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
            success:function(data) {
                if (data.status != 'success') {
                    var error = '';
                    for (var i in data.errors) {
                        error += data.error[i] + ' ';
                    }
                    if (error == '') {
                        error = 'Could not save: Unknown error.';
                    }
                } else {
                    // Switch back to the main view.
                    $('#cms_edit_' + id).show();
                    $('#cms_save_' + id).hide();
                    $('#cms_' + id).hide();
                    $('#cms_display_' + id).html($('#cms_' + id).val()).show();
                }
            },
            error:function() {
                alert('The image could not be saved, please try again later.');
            }
        });
    },
    initDate: function() {
        var self = this;
        $('.cms_edit_date').click(function(e) {
            var id = $(e.target).attr('id').replace(/^cms_edit_/, '');
            self.editDate(id);
        });
        $('.cms_save_date').click(function(e) {
            var id = $(e.target).attr('id').replace(/^cms_save_/, '');
            self.saveDate(id);
        });
    },
    editDate: function(id) {
        $('#cms_edit_' + id).hide();
        $('#cms_save_' + id).show();
        $('#cms_' + id).show();
    },
    saveDate: function(id) {
        $.ajax({
            url: '/admin/cms',
            type: 'POST',
            dataType: 'json',
            data: {
                id: id,
                key: $('#cms_key_' + id).val(),
                column: $('#cms_column_' + id).val(),
                table: $('#cms_table_' + id).val(),
                token: lightning.vars.token,
                action: "update-date",
                date_m: $('#cms_' + id + '_m').val(),
                date_d: $('#cms_' + id + '_d').val(),
                date_y: $('#cms_' + id + '_y').val()
            },
            success:function(data) {
                if (data.status != 'success') {
                    var error = '';
                    for (var i in data.errors) {
                        error += data.error[i] + ' ';
                    }
                    if (error == '') {
                        error = 'Could not save: Unknown error.';
                    }
                } else {
                    // Switch back to the main view.
                    $('#cms_edit_' + id).show();
                    $('#cms_save_' + id).hide();
                    $('#cms_' + id).hide();
                    var newDate = $('#cms_' + id + '_m').val() + '/' + $('#cms_' + id + '_d').val() + '/' + $('#cms_' + id + '_y').val();
                    $('#date_'+id).text(newDate);
                }
            },
            error:function() {
                alert('The image could not be saved, please try again later.');
            }
        });
    }
};
