<?php
use Lightning\Tools\Form;
?>
<form class="validate" action="/optin" method="post">
    <?= Form::renderTokenInput(); ?>
    <div class="panel">
        <input type="hidden" name="redirect" value="<?=!empty($opt_in_redirect) ? $opt_in_redirect : ''; ?>">
        <input type="hidden" name="list_id" value="<?=!empty($opt_in_list_id) ? $opt_in_list_id : 0; ?>">

        First Name: <input name="first" type="text" class="required">
        Last Name: <input name="last" type="text" class="required">
        Email: <input name="email" type="text" class="required email">

        <input type="submit" name="submit" value="Subscribe!" class="button">
    </div>
</form>
