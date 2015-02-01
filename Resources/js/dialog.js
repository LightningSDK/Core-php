lightning.dialog = {
    originalScroll: 0,
    newPosition: 0,
    dialogBox: false,
    dialogBoxLoader: undefined,
    dialogBoxInner: undefined,

    init: function() {
        if (!this.dialogBox) {
            this.dialogBox = $('#dialog_box');
            this.dialogBoxLoader = $('#dialog_box_loader');
            this.dialogBoxInner = $('#dialog_box_inner');
        }
    },

    reposition: function(){
        if (this.dialogBox.is(":visible")) {
            if($(window).height() > this.dialogBox.height()){
                this.newPosition = (($(window).height()-this.dialogBox.height())/2)+$(window).scrollTop();
                this.dialogBox.css("top",this.newPosition+"px");
            } else {
                this.newPosition = this.originalScroll-$(window).scrollTop()+this.dialogBox.position().top;
                var min_pos = Math.max(0,$(window).height()+$(window).scrollTop()-this.dialogBox.height()-20);
                var max_pos = $(window).scrollTop()+10;
                this.newPosition = Math.min(this.newPosition, max_pos);
                this.newPosition = Math.max(this.newPosition, min_pos);
                this.originalScroll = $(window).scrollTop();
                this.dialogBox.css("top",this.newPosition+"px");
            }
        }
    },

    setDialogPosition: function(){
        this.originalScroll = $(window).scrollTop();
        this.reposition();
    },

    showDialogURL: function(url){
        this.showDialog();
        $.ajax({dataType:'HTML',url:url,success:function(data){
            this.setContent(data);
        }});
    },

    /**
     * Stop any fading out (if required) and fade in.
     * @param show_loader
     */
    showDialog: function(show_loader){
        this.dialogBoxLoader.stop(true);
        this.dialogBoxInner.stop(true);
        if(show_loader == "undefined") {
            show_loader = true;
        }
        if(this.dialogBox.is(":visible")){
            var self = this;
            this.dialogBoxInner.fadeOut('fast',function(){
                self.showDialogContainer(show_loader);
            });
        } else {
            this.setDialogPosition();
            this.showDialogContainer(show_loader);
        }
    },

    /**
     * Fades in all dialog components.
     * @param show_loader
     */
    showDialogContainer: function(show_loader){
        this.clear();
        $('#veil').fadeIn('fast');
        this.dialogBoxInner.hide();
        if(show_loader) {
            this.dialogBoxLoader.show();
        } else {
            this.dialogBoxLoader.hide();
        }
        this.dialogBox.fadeIn('fast', this.reposition);
    },

    /**
     * Fades in all dialog components.
     * @param callback
     */
    showPrepared: function(callback){
        this.dialogBoxLoader.stop(true);
        this.dialogBoxInner.stop(true);
        $('#veil').fadeIn('fast');
        var self = this;
        this.dialogBox.fadeOut('fast',function(){
            self.clear();
            if(callback != undefined)
                callback();
            self.dialogBox.fadeIn('fast', this.reposition);
        });
    },

    hide: function(){
        this.dialogBox.fadeOut('fast', function(){
            $('#veil').fadeOut('fast');
        });
    },

    clear: function(){
        this.dialogBoxInner.find('.content').empty().hide();
        this.dialogBoxInner.find('.errors ul').empty();
        this.dialogBoxInner.find('.errors').hide();
        this.dialogBoxInner.find('.messages ul').empty();
        this.dialogBoxInner.find('.messages').hide();
        this.dialogBoxLoader.hide();
    },

    /**
     * Adds new content to a dialog even if it's visible without changing anything else.
     * @param content
     */
    addContent: function(content){
        content = $(content).hide();
        this.dialogBoxInner.find('.content').append(content);
        content.fadeIn('fast');
        this.dialogBoxInner.fadeIn('fast');
        this.dialogBoxInner.find('.content').fadeIn('fast');
        this.reposition();
    },

    /**
     * Resets a dialog with new content. (fades out if required).
     * @param content
     * @param callback
     */
    setContent: function(content, callback){
        var self = this;
        this.dialogBoxLoader.fadeOut('fast', function(){
            this.dialogBoxInner.fadeOut('fast', function(){
                self.showPrepared(function(){
                    this.dialogBoxInner.find('.content').html(content).show();
                    this.dialogBoxInner.fadeIn('fast');
                });
                if(callback) {
                    callback();
                }
            });
        });
        this.reposition();
    },

    /**
     * Add a success message to an existing dialog.
     * @param message
     */
    add: function(message, message_type) {
        message = $('<li>' + message + '</li>');
        var container = (message_type == 'message') ? '.messages' : '.errors';
        var self = this;
        this.dialogBoxLoader.fadeOut('fast', function(){
            if(self.dialogBoxInner.find(container).is(':visible')){
                message.hide();
                self.dialogBoxInner.find(container + ' ul').append(message);
                message.fadeIn("fast");
            } else {
                self.dialogBoxInner.find(container + ' ul').append(message);
                if(self.dialogBoxInner.is(':visible')){
                    self.dialogBoxInner.find(container).fadeIn('fast');
                } else {
                    self.dialogBoxInner.find(container).show();
                }
            }
            self.dialogBoxInner.find(container).fadeIn('fast');
        });
        this.reposition();
    },

    setContent: function(content, callback){
        this.dialogBoxInner.fadeOut('fast',function(){$(this).html(content).fadeIn('fast', callback);})
    },

    hide: function(callback){
        this.dialogBox.fadeOut('fast',function(){$('#veil').fadeOut(callback);})
    }
};
