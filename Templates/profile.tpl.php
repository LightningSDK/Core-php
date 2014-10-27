<?
use Lightning\Tools\ClientUser;
use Lightning\Tools\Form;
?>
<form method="post">
    <?=Form::renderTokenInput(); ?>
    <input type="hidden" name="action" value="save">
    <fieldset>
        <legend>Personal Information:</legend>
        <table>
            <tr>
                <td>
                    First Name:
                </td>
                <td>
                    <input type="text" name="first" value="<?=ClientUser::getInstance()->first;?>">
                </td>
            </tr>
            <tr>
                <td>
                    Last Name:
                </td>
                <td>
                    <input type="text" name="last" value="<?=ClientUser::getInstance()->last;?>">
                </td>
            </tr>
        </table>
    </fieldset>
    <fieldset>
        <legend>Password:</legend>
        <table>
            <tr>
                <td>
                    Current Password:
                </td>
                <td>
                    <input type="password" name="password" value="">
                </td>
            </tr>
            <tr>
                <td>
                    New Password:
                </td>
                <td>
                    <input type="password" name="new_password" value="">
                </td>
            </tr>
            <tr>
                <td>
                    Retype Password:
                </td>
                <td>
                    <input type="password" name="new_password_confirm" value="">
                </td>
            </tr>
        </table>
    </fieldset>
    <input type="submit" name="submit" value="Save" class="button">
</form>