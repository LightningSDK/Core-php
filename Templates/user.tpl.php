<?

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
<?
if (empty($action) || $action == 'join' || $action == 'register'): ?>
    <h2>Create a username and password</h2>
    <form action="/user" method="post" id="register" class="validate">
        <?= Form::renderTokenInput(); ?>
        <table>
            <tr><td>Email:</td><td><input type='text' name='email' class="required email" /></td></tr>
            <tr><td>Password:</td><td><input type='password' name='password' id='password' class="required" /></td></tr>
            <tr><td>Confirm Password:</td><td><input type='password' name='password2' class="required" /></td></tr>
        </table>
        <input type="hidden" name="action" value="register" />
        <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
        <input type="submit" name="submit" value="Register" class="button" />
    </form>

    <br /><br />
<? endif; ?>

<? if (empty($action) || $action == "login"): ?>

    <h2>Log In with your email and password.</h2>

    <form action="/user" method="post" id="register" class="validate">
        <?= Form::renderTokenInput(); ?>
        <table>
            <tr><td>Email:</td><td><input type='text' name='email' class="email required" /></td></tr>
            <tr><td>Password:</td><td><input type='password' name='password' class="required" /></td></tr>
        </table>
        <input type="hidden" name="action" value="login" />
        <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
        <input type="submit" name="submit" value="Log In" class="button" />
        <a href='/user?action=reset'>Forgot your password?</a>
    </form>


    <br /><br />
<? endif; ?>
<? if (!empty($action) && $action == 'reset'): ?>
    <h2>Forgot your password?</h2>

    <form action="/user" method="post" class="validate">
        <?= Form::renderTokenInput(); ?>
        <table>
            <tr>
                <td>Enter your email address:</td>
                <td><input type='text' name='email' class="email required" /></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="reset" />
        <input type="hidden" name="redirect" value="<?=!empty($redirect) ? $redirect : '';?>" />
        <input type="submit" name="submit" value="Reset" class="button" />
    </form>
<? endif; ?>
<? if (!empty($action) && $action == "set_password"): ?>
    <h2>Forgot your password?</h2>

    <form action="/user" method="post" class="validate">
        <?= Form::renderTokenInput(); ?>
        <table>
            <tr><td>New Password:</td><td><input type='password' name='password' id='password' class="required" /></td></tr>
            <tr><td>Confirm Password:</td><td><input type='password' name='password2' class="required" /></td></tr>
        </table>
        <input type="hidden" name="key" value="<?=$key?>" />
        <input type="hidden" name="action" value="set_password" />
        <input type="submit" name="submit" value="Set Password" class="button" />
    </form>
<? endif; ?>
