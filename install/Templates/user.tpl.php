<?php

use Lightning\Tools\Form;

?>

<script language="javascript">

    lightning.formValidation = {
        register: {
            rules: {
                password2: {
                    equalTo: "#password"
                }
            }
        }
    };

</script>
<?php
if (empty($action) || $action == 'join' || $action == 'register'): ?>
    <fieldset>
        <legend>Create a username and password</legend>
        <form action="/user" method="post" id="register" class="validate">
            <?= Form::renderTokenInput(); ?>
            <table class="small-12">
                <tr><td>Email:</td><td><input type='text' name='email' class="required email" /></td></tr>
                <tr><td>Password:</td><td><input type='password' name='password' id='password' class="required" /></td></tr>
                <tr><td>Confirm Password:</td><td><input type='password' name='password2' class="required" /></td></tr>
            </table>
            <input type="hidden" name="action" value="register" />
            <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
            <input type="submit" name="submit" value="Register" class="button" />
        </form>
    </fieldset>
<? endif; ?>

<? if (empty($action) || $action == "login"): ?>
    <fieldset>
        <legend>Log In with your email and password</legend>
        <form action="/user" method="post" id="register" class="validate">
            <?= Form::renderTokenInput(); ?>
            <table class="small-12">
                <tr><td>Email:</td><td><input type='text' name='email' class="email required" /></td></tr>
                <tr><td>Password:</td><td><input type='password' name='password' class="required" /></td></tr>
            </table>
            <input type="hidden" name="action" value="login" />
            <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
            <input type="submit" name="submit" value="Log In" class="button" />
            <a href='/user?action=reset' class="right">Forgot your password?</a>
        </form>
    </fieldset>
<? endif; ?>
<? if (!empty($action) && $action == 'reset'): ?>
    <fieldset>
        <legend>Forgot your password?</legend>
        <form action="/user" method="post" class="validate">
            <?= Form::renderTokenInput(); ?>
            <table class="small-12">
                <tr>
                    <td>Enter your email address:</td>
                    <td><input type='text' name='email' class="email required" /></td>
                </tr>
            </table>
            <input type="hidden" name="action" value="reset" />
            <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
            <input type="submit" name="submit" value="Reset" class="button" />
        </form>
    </fieldset>
<? endif; ?>
<? if (!empty($action) && $action == "set_password"): ?>
    <fieldset>
        <legend>Set your new password:</legend>
        <form action="/user" method="post" class="validate">
            <?= Form::renderTokenInput(); ?>
            <table class="small-12">
                <tr><td>New Password:</td><td><input type='password' name='password' id='password' class="required" /></td></tr>
                <tr><td>Confirm Password:</td><td><input type='password' name='password2' class="required" /></td></tr>
            </table>
            <input type="hidden" name="key" value="<?=$key?>" />
            <input type="hidden" name="action" value="set_password" />
            <input type="submit" name="submit" value="Set Password" class="button" />
        </form>
    </fieldset>
<? endif; ?>
