<?php
use Lightning\Tools\Form;
?>
<style>
    .roledash .medium-4{
        text-align: center;
        padding-bottom: 15px;
    }
    .roledash  .medium-4 table{
        margin: 5px auto 10px;
    }

</style>

<h2>Roles dashboard</h2>
<div class="row roledash">
    <div class="small-12 medium-4 columns">
        <h3>Roles</h3>
        <table>
            <tr><td>id</td><td>name</td></tr>
            <?php foreach ($roles as $role): ?>
                <tr><td><?=$role['role_id'];?></td><td><?=$role['name'];?></td></tr>
            <?php endforeach; ?>
        </table>
        <a href="/admin/roles"class="button small">Edit Roles</a>
    </div>
    <div class="small-12 medium-4 columns">
        <h3>Permissions</h3>
        <table>
            <tr><td>id</td><td>name</td></tr>
            <?php foreach ($permissions as $permission): ?>
                <tr><td><?=$permission['permission_id'];?></td><td><?=$permission['name'];?></td></tr>
            <?php endforeach; ?>
        </table>
        <a href="/admin/permissions" class="button small">Edit permissions</a>
    </div>
    <div class="small-12 medium-4 columns">
        <h3>Upgrade users</h3>
        <p>Click to upgrade users from types to roles</p>
        <form name="roles_form" id="roles_form" action="/admin/rolesdashboard" method="post" class="">
            <input type="hidden" name="action" value="upgraderoles" />
            <?= Form::renderTokenInput(); ?>
            <div class="form-group">
                <input type="submit" name="submit" value="Upgrade" class="button small" />
            </div>
        </form>
    </div>
</div>

