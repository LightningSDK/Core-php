lightning.dialog = {
    originalScroll: 0,
    newPosition: 0,
    dialogBox: false,
    dialogBoxLoader: undefined,
    dialogBoxInner: undefined,

    init: function() {
        if (!this.dialogBox) {
            $('<div id="veil"></div>'+
            '<div id="dialog_box">'+
            '<div class="table_data clear border_top">'+
            '<div class="inner" id="dialog_box_inner">'+
            '<div class="messenger error"><ul></ul></div>'+
            '<div class="messenger message"><ul></ul></div>'+
            '<div class="content"><ul></ul></div>'+
            '</div>'+
            '<div class="inner" id="dialog_box_loader"><p align="center"><img src="/images/coganim2.gif" class="loader_image"></p></div>'+
            '</div>'+
            '</div>').prependTo('body');
            this.dialogBox = $('#dialog_box');
            this.dialogBoxLoader = $('#dialog_box_loader');
            this.dialogBoxInner = $('#dialog_box_inner');
        }
    },

    reposition: function(){
        if (lightning.dialog.dialogBox.is(":visible")) {
            if($(window).height() > lightning.dialog.dialogBox.height()){
                lightning.dialog.newPosition = (($(window).height() - lightning.dialog.dialogBox.height())/2) + $(window).scrollTop();
                lightning.dialog.dialogBox.css("top",lightning.dialog.newPosition+"px");
            } else {
                lightning.dialog.newPosition = lightning.dialog.originalScroll-$(window).scrollTop() + lightning.dialog.dialogBox.position().top;
                var min_pos = Math.max(0,$(window).height()+$(window).scrollTop() - lightning.dialog.dialogBox.height()-20);
                var max_pos = $(window).scrollTop()+10;
                lightning.dialog.newPosition = Math.min(lightning.dialog.newPosition, max_pos);
                lightning.dialog.newPosition = Math.max(lightning.dialog.newPosition, min_pos);
                lightning.dialog.originalScroll = $(window).scrollTop();
                lightning.dialog.dialogBox.css('top', lightning.dialog.newPosition + 'px');
            }
        }
    },

    showPosition: function(){
        this.originalScroll = $(window).scrollTop();
        this.reposition();
    },

    showURL: function(url){
        this.show();
        $.ajax({dataType:'HTML',url:url,success:function(data){
            this.setContent(data);
        }});
    },

    /**
     * Stop any fading out (if required) and fade in.
     * @param show_loader
     */
    show: function(show_loader){
        this.dialogBoxLoader.stop(true);
        this.dialogBoxInner.stop(true);
        if(show_loader == "undefined") {
            show_loader = true;
        }
        if(this.dialogBox.is(":visible")){
            var self = this;
            this.dialogBoxInner.fadeOut('fast',function(){
                self.showContainer(show_loader);
            });
        } else {
            this.showPosition();
            this.showContainer(show_loader);
        }
    },

    /**
     * Fades in all dialog components.
     * @param show_loader
     */
    showContainer: function(show_loader){
        this.clear();
        $('#veil').fadeIn('fast');
        this.dialogBoxInner.hide();
        if(show_loader) {
            this.dialogBoxLoader.show();
        } else {
            this.dialogBoxLoader.hide();
        }
        this.dialogBox.fadeIn('fast', lightning.dialog.reposition);
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
            self.dialogBox.fadeIn('fast', lightning.dialog.reposition);
        });
    },

    hide: function(){
        this.dialogBox.fadeOut('fast', function(){
            $('#veil').fadeOut('fast');
        });
    },

    clear: function(){
        this.dialogBoxInner.find('.content').empty().hide();
        this.dialogBoxInner.find('.error ul').empty();
        this.dialogBoxInner.find('.error').hide();
        this.dialogBoxInner.find('.message ul').empty();
        this.dialogBoxInner.find('.message').hide();
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
        var container = (message_type == 'message') ? '.message' : '.error';
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
