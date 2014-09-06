<?php

use Lightning\Tools\Form;

?>
<h1>Contact</h1>

<script language="javascript">
    $(document).ready(function(){
        $('#contact_form').validate();
    });
</script>

<form action="contact" method="post" id="contact_form">
    <?= Form::renderTokenInput(); ?>

    <p>Contact Us:</p>

    <input type="hidden" name="action" value="sendMessage" />

    Your Name:<br />
    <input type="text" name="name" id='name' value="<?=$postedname?>" class="required" /><br />

    Your Email:<br />
    <input type="text" name="email" id='my_email' value="<?=$postedemail?>" class="required email" /><br />

    Your message:<br />
    <textarea name="message" cols="70" rows="20"><?=$postedmessage?></textarea><br />
    <?=\Lightning\Tools\ReCaptcha::render()?>
    <br />
    <input type="Submit" name="Submit" value="Send Message" class="button" />
</form>
