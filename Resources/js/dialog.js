lightning.dialog = {
    reposition: function(){
        if($('#dialog_box').is(":visible")){
            if($(window).height() > $('#dialog_box').height()){
                var new_position = (($(window).height()-$('#dialog_box').height())/2)+$(window).scrollTop();
                $('#dialog_box').css("top",new_position+"px");
            } else {
                var new_position = $ac.original_scroll-$(window).scrollTop()+$('#dialog_box').position().top;
                var min_pos = Math.max(0,$(window).height()+$(window).scrollTop()-$('#dialog_box').height()-20);
                var max_pos = $(window).scrollTop()+10;
                new_position = Math.min(new_position, max_pos);
                new_position = Math.max(new_position, min_pos);
                $ac.original_scroll = $(window).scrollTop();
                $('#dialog_box').css("top",new_position+"px");
            }
        }
    },

    setDialogPosition: function(){
        $ac.original_scroll = $(window).scrollTop();
        $ac.reposition();
    },

    showDialogURL: function(url){
        $ac.showDialog();
        $.ajax({dataType:'HTML',url:url,success:function(data){
            $ac.setContent(data);
        }});
    },

    /**
     * Stop any fading out (if required) and fade in.
     * @param show_loader
     */
    showDialog: function(show_loader){
        $('#dialog_box_loader').stop(true);
        $('#dialog_box_inner').stop(true);
        if(show_loader == "undefined") show_loader = true;
        if($('#dialog_box').is(":visible")){
            $('#dialog_box_inner').fadeOut('fast',function(){
                $ac.showDialogContainer(show_loader);
            });
        } else {
            $ac.setDialogPosition();
            $ac.showDialogContainer(show_loader);
        }
    },

    /**
     * Fades in all dialog components.
     * @param show_loader
     */
    showDialogContainer: function(show_loader){
        $ac.clear();
        $('#veil').fadeIn('fast');
        $('#dialog_box_inner').hide();
        if(show_loader)
            $('#dialog_box_loader').show();
        else
            $('#dialog_box_loader').hide();
        $('#dialog_box').fadeIn('fast', $ac.reposition);
    },

    /**
     * Fades in all dialog components.
     * @param callback
     */
    showPrepared: function(callback){
        $('#dialog_box_loader').stop(true);
        $('#dialog_box_inner').stop(true);
        $('#veil').fadeIn('fast');
        $('#dialog_box').fadeOut('fast',function(){
            $ac.clear();
            if(callback != undefined)
                callback();
            $('#dialog_box').fadeIn('fast', $ac.reposition);
        });
    },

    hide: function(){
        $('#dialog_box').fadeOut('fast', function(){
            $('#veil').fadeOut('fast');
        });
    },

    /*
     showsetContent: function(callback){
     $("#dialog_")
     $('#dialog_box_inner').fadeOut('fast', function(){
     if(callback != undefined)
     callback();
     });
     },
     */

    clear: function(){
        $("#dialog_box_inner .content").empty().hide();
        $("#dialog_box_inner .errors ul").empty();
        $("#dialog_box_inner .errors").hide();
        $("#dialog_box_inner .messages ul").empty();
        $("#dialog_box_inner .messages").hide();
        $('#dialog_box_loader').hide();
    },

    /**
     * Adds new content to a dialog even if it's visible without changing anything else.
     * @param content
     */
    addContent: function(content){
        content = $(content).hide();
        $('#dialog_box_inner .content').append(content);
        content.fadeIn('fast');
        $('#dialog_box_inner').fadeIn('fast');
        $('#dialog_box_inner .content').fadeIn('fast');
        $ac.reposition();
    },

    /**
     * Resets a dialog with new content. (fades out if required).
     * @param content
     * @param callback
     */
    setContent: function(content, callback){
        $('#dialog_box_loader').fadeOut('fast', function(){
            $('#dialog_box_inner').fadeOut('fast', function(){
                $ac.showPreparedDialog(function(){
                    $('#dialog_box_inner .content').html(content).show();
                    $('#dialog_box_inner').fadeIn('fast');
                });
                if(callback != undefined)
                    callback();
            });
        });
        $ac.reposition();
    },

    /**
     * Add a new error to an existing dialog.
     * @param error
     */
    addError: function(error){
        var new_error = $("<li>"+error+"</li>");
        $('#dialog_box_loader').fadeOut('fast',function(){
            if($("#dialog_box_inner .errors").is(":visible")){
                new_error.hide();
                $("#dialog_box_inner .errors ul").append(new_error);
                new_error.fadeIn("fast");
            } else {
                $("#dialog_box_inner .errors ul").append(new_error);
                if($("#dialog_box_inner").is(":visible")){
                    $("#dialog_box_inner .errors").fadeIn("fast");
                } else {
                    $("#dialog_box_inner .errors").show();
                }
            }
            $("#dialog_box_inner").fadeIn("fast");
        });
        $ac.reposition();
    },

    /**
     * Add a success message to an existing dialog.
     * @param message
     */
    addMessage: function(message){
        message = $("<li>"+message+"</li>");
        $('#dialog_box_loader').fadeOut('fast',function(){
            if($("#dialog_box_inner .messages").is(":visible")){
                message.hide();
                $("#dialog_box_inner .messages ul").append(message);
                message.fadeIn("fast");
            } else {
                $("#dialog_box_inner .messages ul").append(message);
                if($("#dialog_box_inner").is(":visible")){
                    $("#dialog_box_inner .messages").fadeIn("fast");
                } else {
                    $("#dialog_box_inner .messages").show();
                }
            }
            $("#dialog_box_inner").fadeIn("fast");
        });
        $ac.reposition();
    },

    setContent: function(content, callback){
        $('#dialog_box_inner').fadeOut('fast',function(){$(this).html(content).fadeIn('fast',callback);})
    },

    hide: function(callback){
        $('#dialog_box').fadeOut('fast',function(){$('#veil').fadeOut(callback);})
    }
};
