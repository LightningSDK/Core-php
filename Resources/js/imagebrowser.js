lightning.imageBrowser = {
    path: '',
    web_path: '',
    container: '',
    images: [],
    /**
     * For use with CKEditor
     */
    funcNum: 0,
    init: function() {
        this.container = lightning.vars.imageBrowser.container;
        this.loadImages();
        this.loadFolders('');

        $('.folders').on('click', '.folder', lightning.imageBrowser.changePath);
        $('.images').on('dblclick', '.thumb-container', lightning.imageBrowser.select);

        var url = window.location.search.match(/CKEditorFuncNum=([0-9]+)/);
        if (url[1]) {
            this.funcNum = url[1];
        }
    },
    loadFolders: function(path) {
        $.ajax({
            url: '/imageBrowser',
            data: {
                'action': 'folders',
                'path': lightning.imageBrowser.container + ':' + path
            },
            dataType: 'JSON',
            success: lightning.imageBrowser.populateFolders
        });
    },
    loadImages: function() {
        $.ajax({
            url: '/imageBrowser',
            data: {
                'action': 'images',
                'path': lightning.imageBrowser.container + ':' + lightning.imageBrowser.path
            },
            success: lightning.imageBrowser.populateImages
        });
    },
    changePath: function(event) {
        var newPath = [];
        var folder = $(this);
        // Build the path array.
        do {
            // Do not include the root folder
            if (folder.prop('id') != "") {
                newPath.unshift(folder.prop('id'));
            }
            folder = folder.parent().closest('.folder');
        } while (folder.length > 0);

        // Reload the image list.
        lightning.imageBrowser.path = newPath.join('/');
        lightning.imageBrowser.loadImages();

        // Make sure the parent folders don't click.
        event.stopPropagation();
    },
    populateFolders: function(data) {
        // Find the parent container
        var path = data.path.split('/');
        var container = $('.folders [data-root=true] .children');
        if (path != '') {
            for (var i in path) {
                container = container.find('.children #'+path[i]);
                if (container.length == 0) {
                    return;
                }
            }
        }
        // Add the directories here
        container.empty();
        for (var i in data.folders) {
            container.append('<div class="folder" id="' + data.folders[i] + '"><i class="fa fa-chevron-down"></i> ' + data.folders[i] + '<div class="children"></div></div>');
        }
    },
    populateImages: function(data) {
        if (data.path != lightning.imageBrowser.path) {
            // This is an old request.
            return;
        }
        // Add the images to the display.
        var image_container = $('.image-browser .images').empty();
        lightning.imageBrowser.images = data.images;
        lightning.imageBrowser.web_path = data.web_path;
        for (var i in data.images) {
            image_container.append('<div class="thumb-container" data-img-id="' + i + '"><img src="' + data.web_path + '.thumbs/' + data.images[i].filename + '"><div class="data"><span class="filename">' + data.images[i].filename + '</span><span class="dimensions">' + data.images[i].width + 'x' + data.images[i].height +'</span><span>' + lightning.format.dataSize(data.images[i].filesize) + '</span></div></div>');
        }
    },
    select: function() {
        var url = lightning.imageBrowser.web_path + lightning.imageBrowser.images[$(this).data('img-id')].filename;
        window.opener.CKEDITOR.tools.callFunction( lightning.imageBrowser.funcNum, url );
        window.close();
    }
};
