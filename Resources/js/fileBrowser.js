lightning.fileBrowser = {
    type: null,
    field: null,
    elf: null,
    funcNum: null,
    init: function() {
        var query = document.location.search.substring(1).split('&');
        for (var i in query) {
            var split = query[i].split('=');
            if (split[0] == 'type') {
                lightning.fileBrowser.type = split[1];
            }
            if (split[0] == 'field') {
                lightning.fileBrowser.field = split[1];
            }
            if (split[0] == 'CKEditorFuncNum') {
                lightning.fileBrowser.funcNum = split[1];
            }
        }

        // TODO: This has to be changed to handle other browsers.
        lightning.fileBrowser.elf = $('#elfinder').elfinder({
            // set your elFinder options here
            url: '/elfinder',  // connector URL
            closeOnEditorCallback: true,
            getFileCallback: lightning.fileBrowser.select
        }).elfinder('instance');
    },

    select: function(file) {
        switch (lightning.fileBrowser.type) {
            case 'ckeditor':
                window.opener.CKEDITOR.tools.callFunction(lightning.fileBrowser.funcNum, file.url);
                window.close();
                break;
            case 'tinymce':
                // pass selected file path to TinyMCE
                parent.tinymce.activeEditor.windowManager.getParams().setUrl(file.url);

                // force the TinyMCE dialog to refresh and fill in the image dimensions
                var t = parent.tinymce.activeEditor.windowManager.windows[0];
                t.find('#src').fire('change');

                // close popup window
                parent.tinymce.activeEditor.windowManager.close();
                break;
            case 'lightning-field':
                window.opener.lightning.fileBrowser.fieldSelected(file.url, lightning.fileBrowser.field);
                window.close();
                break;
            case 'lightning-cms':
                break;
        }
    },

    openSelect: function(field) {
        window.open(
            '/js/elfinder/elfinder.html?type=lightning-field&field=' + field,
            'Image Browser',
            ''
        );
    },
    fieldSelected: function(url, field) {
        var imageField = $('#file_browser_image_' + field);
        if (imageField.length > 0) {
            imageField.prop('src', url).show();
        }
        $('#' + field).val(url);
    },
    clear: function(field) {
        var imageField = $('#file_browser_image_' + field);
        if (imageField.length > 0) {
            imageField.prop('src', '').hide();
        }
        $('#' + field).val('');
    }
};
