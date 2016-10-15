<?php

use Lightning\Tools\Form;
use Lightning\View\Field;
use Lightning\Tools\ReCaptcha;

?>
<div class="row">
    <h1>Contact</h1>

    <form method="post" id="contact_form" data-abide>
        <div>
            <label>Your Name:
                <input type="text" name="name" id='name' value="<?=Field::defaultValue('name');?>" required />
            </label>
            <small class="error">Please enter your name.</small>
        </div>

        <div>
            <label>
                Your Email:
                <input type="email" name="email" id='my_email' value="<?=Field::defaultValue('email');?>" required />
            </label>
            <small class="error">Please enter a valid email address.</small>
        </div>

        <div>
            <label>
                Your message:
                <textarea name="message" cols="70" rows="5"><?=Field::defaultValue('name', null, 'text');?></textarea><br />
            </label>
        </div>
        <?=ReCaptcha::render()?>
        <br />
        <input type="hidden" name="contact" value="true" />
        <input type="Submit" name="Submit" value="Send Message" class="button" />
    </form>
</div>
