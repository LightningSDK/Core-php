(function() {
    var self;
    lightning.cms = {
        edit: function (editor) {
            lightning.htmleditor.initEditor(editor);
            $('#' + editor.replace(/^cms_/, 'cms_edit_')).hide();
            $('#' + editor.replace(/^cms_/, 'cms_save_')).show();
        },

        save: function (editor) {
            lightning.dialog.showLoader('Saving...');
            lightning.htmleditor.deactivateEditor(editor);
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
                success: function (data) {
                    $('#' + editor.replace(/^cms_/, 'cms_edit_')).show();
                    $('#' + editor.replace(/^cms_/, 'cms_save_')).hide();
                    lightning.dialog.clear();
                    lightning.dialog.add('Saved!', 'message');
                },
                error: function () {
                    self.edit(editor);
                    lightning.dialog.clear();
                    lightning.dialog.add('The content could not be saved, please try again!', 'error');
                }
            });
        },

        initImage: function () {
            $('.cms_edit_image').on('click', function (e) {
                var id = $(e.target).attr('id').replace(/^cms_edit_/, '');
                self.editImage(id);
            });
            $('.cms_save_image').on('click', function (e) {
                var id = $(e.target).attr('id').replace(/^cms_save_/, '');
                self.saveImage(id);
            });
            $('.imagesCSS').keyup(function () {
                var textField = $(this);
                var id = textField.attr('id').replace('_class', '');
                var classes = textField.attr('name') + ' ' + textField.val();
                $('#' + id).removeClass().addClass(classes);
            });
        },

        editImage: function (id) {
            $('#cms_save_' + id).show();
            $('#cms_' + id + '_class').show();
            lightning.fileBrowser.openSelect('lightning-cms', id);
        },

        imageSelected: function (fileUrl, id) {
            $('#cms_' + id).attr('src', fileUrl);
        },

        saveImage: function (id) {
            lightning.dialog.showLoader('Saving...');
            $('#cms_save_' + id).hide();
            $('#cms_' + id + '_class').hide();
            $.ajax({
                url: '/admin/cms',
                type: 'POST',
                dataType: 'json',
                data: {
                    cms: id,
                    class: $('#cms_' + id + '_class').val(),
                    token: lightning.vars.token,
                    action: "save-image",
                    content: $('#cms_' + id).attr('src').replace(lightning.vars.cms.baseUrl, '')
                },
                success: function() {
                    lightning.dialog.clear();
                    lightning.dialog.add('Saved!', 'message');
                },
                error: function () {
                    // Revert back to edit mode.
                    $('#cms_save_' + id).show();
                    $('#cms_' + id + '_class').show();
                    lightning.dialog.clear();
                    lightning.dialog.add('The content could not be saved, please try again!', 'error');
                }
            });
        },

        initPlain: function () {
            $('.cms_edit_plain').on('click', function (e) {
                var id = $(e.target).attr('id').replace(/^cms_edit_/, '');
                self.editPlain(id);
            });
            $('.cms_save_plain').on('click', function (e) {
                var id = $(e.target).attr('id').replace(/^cms_save_/, '');
                self.savePlain(id);
            });
        },

        editPlain: function (id) {
            $('#cms_edit_' + id).hide();
            $('#cms_save_' + id).show();
            $('#cms_display_' + id).hide();
            $('#cms_' + id).show();
        },

        savePlain: function (id) {
            lightning.dialog.showLoader('Saving...');
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
                success: function () {
                    // Switch back to the main view.
                    $('#cms_edit_' + id).show();
                    $('#cms_save_' + id).hide();
                    $('#cms_' + id).hide();
                    $('#cms_display_' + id).html($('#cms_' + id).val()).show();
                    lightning.dialog.clear();
                    lightning.dialog.add('Saved!', 'message');
                },
                error: function() {
                    lightning.dialog.clear();
                    lightning.dialog.add('The content could not be saved, please try again!', 'error');
                }
            });
        },
        initDate: function () {
            $('.cms_edit_date').on('click', function (e) {
                var id = $(e.target).attr('id').replace(/^cms_edit_/, '');
                self.editDate(id);
            });
            $('.cms_save_date').on('click', function (e) {
                var id = $(e.target).attr('id').replace(/^cms_save_/, '');
                self.saveDate(id);
            });
        },
        editDate: function (id) {
            $('#cms_edit_' + id).hide();
            $('#cms_save_' + id).show();
            $('#cms_' + id).show();
        },
        saveDate: function (id) {
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
                success: function (data) {
                    var error = '';
                    for (var i in data.errors) {
                        error += data.error[i] + ' ';
                    }
                    if (error == '') {
                        error = 'Could not save: Unknown error.';
                    }
                },
                error: function () {
                    // Switch back to the main view.
                    $('#cms_edit_' + id).show();
                    $('#cms_save_' + id).hide();
                    $('#cms_' + id).hide();
                    var newDate = $('#cms_' + id + '_m').val() + '/' + $('#cms_' + id + '_d').val() + '/' + $('#cms_' + id + '_y').val();
                    $('#date_' + id).text(newDate);
                }
            });
        }
    };
    self = lightning.cms;
})();
