

/* BLOG FUNCTIONS */
function submit_comment(blog_id) {
    var email = $('#blog_comment_email').val();
    var name = $('#blog_comment_name').val();
    var web = $('#blog_comment_web').val();
    var comment = $('#blog_new_comment').val();
    $.ajax({
        url: "/blog.php",
        data: {"action":"post_comment_check","name":name,"email":email,"web":web,"comment":comment,"blog_id":blog_id},
        type: "POST",
        dataType: "html",
        success: function(data) {
            $.ajax({
                url: "blog.php",
                data: {"action":"post_comment",
                    "name":name,
                    "email":email,
                    "comment":comment,
                    "web":web,
                    "blog_id":blog_id,
                    "check_val":data},
                type: "POST",
                dataType: "html",
                success: function(data) {
                    var new_comment = $('#blog_comment_blank').clone().attr('id','').hide();
                    new_comment.find('.blog_comment_body').html(comment);
                    new_comment.find('.blog_comment_name').html("By "+name);
                    date = new Date();
                    new_comment.find('.blog_comment_date').html("just posted");
                    $('#blog_comment_container').prepend(new_comment);
                    $('#new_comment_box').fadeOut(function() {
                        $('#blog_comment_container .blog_comment').fadeIn("slow");
                    });
                }
            })
        }
    });
}

function edit_blog() {
    $('#blog_edit').show();
    $('#blog_body').hide();
}

function delete_blog_comment(comment_id) {
    $.ajax({
        url:"/blog.php",
        data:{"action":"remove_blog_comment","blog_comment_id":comment_id},
        type:"POST",
        dataType:"html",
        success:function(data) {
            if (data == "ok")
                $('#blog_comment_'+comment_id).fadeOut();
        }
    });
}

function approve_blog_comment(comment_id) {
    $.ajax({
        url:"blog.php",
        data:{"action":"approve_blog_comment","blog_comment_id":comment_id},
        type:"POST",
        dataType:"html",
        success:function(data) {
            if (data == "ok")
                $('#blog_comment_'+comment_id+" .approve_comment").fadeOut();
        }
    });
}
