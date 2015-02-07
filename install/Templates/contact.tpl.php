<?php

use Lightning\Tools\Form;
use Lightning\View\Field;
use Lightning\Tools\ReCaptcha;

?>
<h1>Contact</h1>

<form action="contact" method="post" id="contact_form" class="validate">
    <?= Form::renderTokenInput(); ?>

    <p>Contact Us:</p>

    <input type="hidden" name="action" value="sendMessage" />

    Your Name:<br />
    <input type="text" name="name" id='name' value="<?=Field::defaultValue('name');?>" class="required" /><br />

    Your Email:<br />
    <input type="text" name="email" id='my_email' value="<?=Field::defaultValue('email');?>" class="required email" /><br />

    Your message:<br />
    <textarea name="message" cols="70" rows="20"><?=Field::defaultValue('name', null, 'text');?></textarea><br />
    <?=ReCaptcha::render()?>
    <br />
    <input type="Submit" name="Submit" value="Send Message" class="button" />
</form>
