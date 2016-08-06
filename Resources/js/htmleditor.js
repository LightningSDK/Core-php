(function(){
    var self = lightning.htmleditor = {
        editors: {},
        /**
         * Initialize editor settings.
         */
        init: function() {
            var editors = lightning.vars.htmleditors;
            for (var i in editors) {
                if (editors[i].editor_type == 'tinymce') {
                    editors[i].setup = self.tinymceSetup;
                    if (editors[i].browser) {
                        editors[i].file_browser_callback = self.imageBrowser;
                    }

                    // Activate any startup editors.
                    if (editors[i].startup) {
                        self.initEditor(i);
                    }
                } else if (editors[i].editor_type == 'ckeditor') {
                    var x = function(j){
                        CKEDITOR.scriptLoader.queue(CKEDITOR.getUrl('config.js'), function(){
                            // Init the CKEditor Config
                            if (typeof CKEDITOR.editorConfig == "function") {
                                CKEDITOR.editorConfig(CKEDITOR.config);
                            }

                            // Add any plugins specified for just this editor.
                            if (editors[j].hasOwnProperty('plugins')) {
                                editors[j].plugins = editors[j].plugins.replace(/\*/, CKEDITOR.config.plugins);
                            } else {
                                editors[j].plugins = CKEDITOR.config.plugins;
                            }

                            // If fullPage is requested, the divarea plugin must be removed.
                            if (editors[j].hasOwnProperty('fullPage') && editors[j].fullPage) {
                                editors[j].plugins = editors[j].plugins.replace('divarea', '');
                            }

                            // Activate any startup editors.
                            if (editors[j].startup) {
                                self.initEditor(j);
                            }
                        });
                    }(i);
                }
            }
            // For div based editors, a presave function must be called to include it in the form.
            $('.html_editor_presave').closest('form').submit(function(){
                $(this).find('.html_editor_presave').each(function(){
                    var field = $(this);
                    var id = $(this).prop('id').replace('save_', '');
                    if (editors[id].editor_type == 'ckeditor') {
                        field.val(self.getContent(id));
                    }
                });
            });
        },

        /**
         * Get the content of an editor.
         *
         * @param {string} id
         *   The name of the editor.
         *
         * @returns {string}
         *   The content.
         */
        getContent: function(id) {
            if (lightning.vars.htmleditors[id].editor_type == 'ckeditor') {
                return self.editors[id].getData();
            }
        },

        /**
         * Activate the editor.
         *
         * TODO: Change this to 'activateEditor'
         */
        initEditor: function(editor_id) {
            var editor = $('#' + editor_id);
            var editor_settings = lightning.vars.htmleditors[editor_id];
            if (editor_settings.content_rendered) {
                // This has rendered markup, replace with the original markup.
                editor.html(editor_settings.content);
                // Remove the rendered content since it will no longer be relevant
                // and will result in the original replacing the edited content.
                delete editor_settings.content_rendered;
            }
            if (editor_settings.editor_type == 'tinymce') {
                tinymce.init(editor_settings);
            } else if (editor_settings.editor_type == 'ckeditor') {
                editor.attr('contenteditable', 'true');
                self.editors[editor_id] = CKEDITOR.inline(editor_id, editor_settings);
            }
        },

        /**
         * Deactivate an editor.
         *
         * @param integer editor_id
         *   The name of the editor.
         */
        deactivateEditor: function(editor_id) {
            if (lightning.vars.htmleditors[editor_id].editor_type == 'tinymce') {
                for (var i in tinymce.editors) {
                    if (tinymce.editors[i].id == editor_id) {
                        tinymce.editors[i].remove();
                    }
                }
            } else if (lightning.vars.htmleditors[editor_id].editor_type == 'ckeditor') {
                $('#' + editor_id).attr('contenteditable', 'false');
                self.editors[editor_id].destroy();
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
})();
