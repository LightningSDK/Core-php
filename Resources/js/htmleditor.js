lightning.htmleditor = {
    init: function() {
        for (var i in lightning.vars.htmleditors) {
            if (lightning.vars.htmleditors[i].editor_type == 'tinymce') {
                lightning.vars.htmleditors[i].setup = lightning.htmleditor.tinymceSetup;
                if (lightning.vars.htmleditors[i].browser) {
                    lightning.vars.htmleditors[i].file_browser_callback = lightning.htmleditor.imageBrowser;
                }
            }
            if (lightning.vars.htmleditors[i].startup) {
                lightning.htmleditor.initEditor(i);
            }
        }
        // For div based editors, a presave function must be called to include it in the form.
        $('.html_editor_presave').closest('form').submit(function(){
            $(this).find('.html_editor_presave').each(function(){
                var field = $(this);
                var id = $(this).prop('id').replace('save_', '');
                if (lightning.vars.htmleditors[id].editor_type == 'ckeditor') {
                    field.val(lightning.vars.htmleditors[id].ckeditor.getData());
                }
            });
        });
    },

    initEditor: function(editor_id) {
        if (lightning.vars.htmleditors[editor_id].editor_type == 'tinymce') {
            tinymce.init(lightning.vars.htmleditors[editor_id]);
        } else if (lightning.vars.htmleditors[editor_id].editor_type == 'ckeditor') {
            if (lightning.vars.htmleditors[editor_id].hasOwnProperty('plugins')) {
                lightning.vars.htmleditors[editor_id].plugins = lightning.vars.htmleditors[editor_id].replace(/\*/, CKEDITOR.plugins);
            }
            lightning.vars.htmleditors[editor_id].ckeditor = CKEDITOR.replace(editor_id, lightning.vars.htmleditors[editor_id]);
        }
    },

    destroyEditor: function(editor_id) {
        if (lightning.vars.htmleditors[editor_id].editor_type == 'tinymce') {
            for (var i in tinymce.editors) {
                if (tinymce.editors[i].id == editor_id) {
                    tinymce.editors[i].remove();
                }
            }
        } else if (lightning.vars.htmleditors[editor_id].editor_type == 'ckeditor') {
            lightning.vars.htmleditors[editor_id].ckeditor.destroy();
        }
    },

    tinymceSetup: function(editor) {
        editor.addButton('columns', {
            type: 'menubutton',
            text: 'Columns',
            icon: false,
            menu: [{
                text: '2 Columns',
                onclick: function() {
                    editor.insertContent('<div class="row"><div class="small-12 medium-6 column"><p>Column 1</p></div><div class="small-12 medium-6 column"><p>Column 2</p></div></div>');
                }
            }, {
                text: '3 Columns',
                onclick: function() {
                    editor.insertContent('<div class="row"><div class="small-12 medium-4 column"><p>Column 1</p></div><div class="small-12 medium-4 column"><p>Column 2</p></div><div class="small-12 medium-4 column"><p>Column 3</p></div></div>');
                }
            }, {
                text: '4 Columns',
                onclick: function() {
                    editor.insertContent('<div class="row"><div class="small-12 medium-3 column"><p>Column 1</p></div><div class="small-12 medium-3 column"><p>Column 2</p></div><div class="small-12 medium-3 column"><p>Column 3</p></div><div class="small-12 medium-3 column"><p>Column 4</p></div></div>');
                }
            }]
        });
        editor.addButton('yt', {
            type: 'button',
            text: 'YT',
            icon: false,
            onclick: function(){
                editor.insertContent('{{youtube id="" flex=true widescreen=true}}');
            },
        });
    },
    imageBrowser: function(field_name, url, type, win) {
        tinymce.activeEditor.windowManager.open({
            //file: '/imageBrowser?container=' + tinymce.activeEditor.settings.browser_container,// use an absolute path!
            file: '/elfinder/elfinder.html?type=tinymce',
            title: 'Image Browser',
            width: 900,
            height: 450,
            resizable: 'yes'
        }, {
            setUrl: function (url) {
                win.document.getElementById(field_name).value = url;
            }
        });
        return false;
    }
};
