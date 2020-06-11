<?php

use lightningsdk\core\Tools\Form;
use lightningsdk\core\View\Field;

if (empty($action) || $action == 'join' || $action == 'register'): ?>
    <fieldset>
        <legend>Create a username and password</legend>
        <form action="/user" method="post" id="register" data-abide>
            <?= Form::renderTokenInput(); ?>
            <div>
                <label>Your Name:
                    <input type="text" name="name" id='name' value="<?=Field::defaultValue('name');?>" required />
                </label>
                <small class="form-error">Please enter your name.</small>
            </div>
            <div>
                <label>Your Email:
                    <input type="email" name="email" id='email' value="<?=Field::defaultValue('email');?>" required />
                </label>
                <small class="form-error">Please enter your email.</small>
            </div>
            <div>
                <label>Create a Password:
                    <input type="password" name="password" id='password' value="" pattern="[a-zA-Z0-9]+" required />
                </label>
                <small class="form-error">The password must be at least 8 characters and contain at least one number.</small>
            </div>
            <div>
                <label>Confirm your Password:
                    <input type="password" name="password2" id='password2' value="" data-equalto="password" required />
                </label>
                <small class="form-error">Please enter the same password.</small>
            </div>
            <input type="hidden" name="action" value="register" />
            <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
            <input type="submit" name="submit" value="Register" class="button" />
        </form>
    </fieldset>
<?php endif; ?>

<?php if (empty($action) || $action == "login"): ?>
    <fieldset>
        <legend>Log In with your email and password</legend>
        <form action="/user" method="post" id="register" data-abide>
            <?= Form::renderTokenInput(); ?>
            <div>
                <label>Your Email:
                    <input type="email" name="email" id='email' value="<?=Field::defaultValue('email');?>" required />
                </label>
                <small class="form-error">Please enter your email.</small>
            </div>
            <div>
                <label>Your password:
                    <input type="password" name="password" id='password' value="" required />
                </label>
                <small class="form-error">Please enter your password.</small>
            </div>
            <input type="hidden" name="action" value="login" />
            <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
            <input type="submit" name="submit" value="Log In" class="button" />
            <a href='/user?action=reset' class="right">Forgot your password?</a>
        </form>
    </fieldset>
<?php endif; ?>
<?php if (!empty($action) && $action == 'reset'): ?>
    <fieldset>
        <legend>Forgot your password?</legend>
        <form action="/user" method="post" data-abide>
            <?= Form::renderTokenInput(); ?>
            <div>
                <label>Your Email:
                    <input type="email" name="email" id='email' value="<?=Field::defaultValue('email');?>" required />
                </label>
                <small class="form-error">Please enter your email.</small>
            </div>
            <input type="hidden" name="action" value="reset" />
            <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
            <input type="submit" name="submit" value="Reset" class="button" />
        </form>
    </fieldset>
<?php endif; ?>
<?php if (!empty($action) && $action == "set_password"): ?>
    <fieldset>
        <legend>Set your new password:</legend>
        <form action="/user" method="post" data-abide>
            <?= Form::renderTokenInput(); ?>
            <div>
                <label>Create a Password:
                    <input type="password" name="password" id='password' value="" pattern="[a-zA-Z0-9]+" required />
                </label>
                <small class="form-error">The password must be at least 8 characters and contain at least one number.</small>
            </div>
            <div>
                <label>Confirm your Password:
                    <input type="password" name="password2" id='password2' value="" data-equalto="password" required />
                </label>
                <small class="form-error">Please enter the same password.</small>
            </div>
            <input type="hidden" name="key" value="<?= $key; ?>" />
            <input type="hidden" name="action" value="set_password" />
            <input type="submit" name="submit" value="Set Password" class="button" />
        </form>
    </fieldset>
<?php endif; ?>
