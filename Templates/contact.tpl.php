<?php

use Lightning\Tools\Configuration;
use Lightning\Tools\Form;
use Lightning\View\Field;
use Lightning\Tools\ReCaptcha;

?>
<div class="row">
    <h1>Contact</h1>

    <form method="post" id="contact_form" data-abide>
        <?= Form::renderTokenInput(); ?>
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
        <input type="hidden" name="contact" value="true" />
        <?php if (Configuration::get('recaptcha.invisible.public')) : ?>
        <?=ReCaptcha::renderInvisible('Send Message', 'button');?>
        <?php elseif (Configuration::get('recaptcha.public')): ?>
            <?=ReCaptcha::render()?>
            <br />
            <input type="Submit" name="Submit" value="Send Message" class="button" />
        <?php else: ?>
            <input type="Submit" name="Submit" value="Send Message" class="button" />
        <?php endif; ?>
    </form>
</div>
