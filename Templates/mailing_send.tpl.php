<h1>Sending Email</h1>
<script type="text/javascript" src="/js/jquery.stream.js"></script>
<script language="javascript">
function send_emails (type){
    //START AJAX TRANSMISSION HERE
    $('#message_status').html('Starting ...\n');
    $('.mail_buttons').fadeOut();
    var last_response_len = 0;
    $.ajax({
        url: '/admin/mailing/send?action=send-' + type + '&id=<?=$message['message_id']?>',
        dataType: 'text',
        type: 'post',
        stream: true,
        xhrFields: {
            onprogress: function(e) {
                var response = e.currentTarget.response;
                addContent('#message_status', response.substring(last_response_len));
                last_response_len = response.length;
            }
        },
        success: function(data) {
            addContent('#message_status', data.substring(last_response_len));
            $('.mail_buttons').fadeIn();
        },
        error: function() {
            $('.mail_buttons').fadeIn();
        }
    });
}
function addContent(container, content) {
    $container = $(container);
    $container.html($container.html() + content);
    $container.animate({ scrollTop: $container.attr("scrollHeight") }, 500);
}
</script>

<h3>Status:</h3>
<div class="mail_buttons">
<input type="button" id='start_button' class="button" value="Send to All" onclick="send_emails('all')" />
<input type="button" id='test_button' class="button" value="Send Test" onclick="send_emails('test')" />
<input type="button" id='test_button' class="button" value="Send Count" onclick="send_emails('count')" />
<input type="button" id='edit_button' class="button" value="Edit" onclick="document.location='/admin/mailing/messages?action=edit&id=<?=$message['message_id']?>'" />
</div>
<pre id='message_status' style="width:100%; height: 300px; overflow:auto; border:1px solid grey;">
    Ready ...
</pre>

<h3>Subject:</h3>
    <p><?=$message['subject'];?></p>
<h3>Message:</h3>
<div style="width:100%; height: 300px; overflow:auto; border:1px solid grey;">
    <?=$message['body']?>
</div>
