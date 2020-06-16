( function() {

    CKEDITOR.plugins.add( 'lightning-video', {
        icons: 'lightning-video',
        init: function( editor ) {
            editor.addCommand( 'lightning-video', {
                exec: function( editor ) {
                    editor.fire('saveSnapshot');
                    editor.insertText('{{youtube id="" flex=true widescreen=true}}');
                    editor.fire('saveSnapshot');
                }
            });
            editor.ui.addButton( 'lightning-video', {
                label: 'Youtube Video',
                command: 'lightning-video',
                toolbar: 'insert'
            });
        }
    });

} )();
