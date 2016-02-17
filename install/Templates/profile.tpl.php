<?php
use Lightning\Tools\ClientUser;
use Lightning\Tools\Form;
use Lightning\View\Field\Checkbox;

?>
<form method="post">
    <?=Form::renderTokenInput(); ?>
    <input type="hidden" name="action" value="save">
    <fieldset>
        <legend>Personal Information:</legend>
        <table class="small-12">
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
        <table class="small-12">
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
    <?php if ($mailing_lists): ?>
        <fieldset>
            <legend>Subscriptions:</legend>
            <table class="small-12">
                <tr>
                    <td>List</td>
                    <td>Subscribed</td>
                </tr>
                <?php
                $subscribe_other_active = false;
                foreach ($all_lists as $list):
                    if (!empty($list['name']) && !empty($list['visible'])): ?>
                        <tr>
                            <td><?= $list['name'] ?></td>
                            <td><?= Checkbox::render('subscribed[' . $list['message_list_id'] . ']', $list['message_list_id'], isset($mailing_lists[$list['message_list_id']])); ?></td>
                        </tr>
                    <?php else:
                        $subscribe_other_active |= isset($mailing_lists[$list['message_list_id']]);
                    endif;
                endforeach; ?>
                <?php if ($subscribe_other_active): ?>
                    <tr>
                        <td>Other</td>
                        <td><?= Checkbox::render('subscribed[]', '0', $subscribe_other_active); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </fieldset>
    <?php endif; ?>
    <input type="submit" name="submit" value="Save" class="button">
</form>
