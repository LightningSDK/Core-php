lightning.tinymce = {
    init: function() {
        for (var i in lightning.vars.tinymce) {
            lightning.vars.tinymce[i].setup = lightning.tinymce.setup;
            if (lightning.vars.tinymce[i].browser) {
                lightning.vars.tinymce[i].file_browser_callback = lightning.tinymce.imageBrowser;
            }
            lightning.vars.tinymce[i].template_popup_height = 300;
            lightning.vars.tinymce[i].template_popup_height = 500;
            if (lightning.vars.tinymce[i].startup) {
                lightning.tinymce.initEditor(i);
            }
        }
    },
    initEditor: function(editor_id) {
        tinymce.init(lightning.vars.tinymce[editor_id]);
    },
    destroyEditor: function(editor_id) {
        for (var i in tinymce.editors) {
            if (tinymce.editors[i].id == editor_id) {
                tinymce.editors[i].remove();
            }
        }
    },
    setup: function(editor) {
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
