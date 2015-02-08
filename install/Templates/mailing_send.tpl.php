<h1>Sending Email</h1>

<h3>Status:</h3>
<div class="mail_buttons">
    <input type="button" id='start_button' class="button red" value="Send to All" onclick="lightning.admin.messages.send('all')" />
    <input type="button" id='test_button' class="button" value="Send Test" onclick="lightning.admin.messages.send('test')" />
    <input type="button" id='test_button' class="button" value="Send Count" onclick="lightning.admin.messages.send('count')" />
    <input type="button" id='edit_button' class="button" value="Edit" onclick="document.location='/admin/mailing/messages?action=edit&id=<?=$message['message_id']?>'" />
</div>
<pre id='message_status'>
    Ready ...
</pre>

<h3>Subject:</h3>
<p><?=$message['subject'];?></p>
<h3>Message:</h3>
<div style="width:100%; height: 300px; overflow:auto; border:1px solid grey;">
    <?=$message['body']?>
</div>
