<form method="post" action="/admin/social/share">
    <h3>Social Networks</h3>
    <table id="networks">
        <thead>
        <tr>
            <td><input type="checkbox" class="all" id="checkbox_netwroks" data-all-container="#networks" data-all-items="input.network"></td>
            <td>Network</td>
            <td>Name</td>
            <td>Screen Name</td>
        </tr>
        </thead>
        <?php foreach ($authorizations as $auth): ?>
            <tr>
                <td><input type="checkbox" name="network[]" class="network" value="<?=$auth['social_auth_id'];?>"></td>
                <td><?= $auth['network']; ?></td>
                <td><?=$auth['name'];?></td>
                <td><?=$auth['screen_name'];?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php if (!empty($facebook_pages)): ?>
        <h3>Facebook Pages</h3>
        <table id="facebook_pages">
            <thead>
            <tr>
                <td>
                    <input type="checkbox" class="all" id="checkbox_pages" data-all-container="#facebook_pages" data-all-items="input.facebook">
                </td>
                <td>Name</td>
            </tr>
            </thead>
            <?php foreach ($facebook_pages as $page): ?>
                <tr>
                    <td><input type="checkbox" name="facebook[]" class="facebook" value="<?=$page->id;?>"></td>
                    <td><?=$page->name;?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <?= \Lightning\Tools\Form::renderTokenInput(); ?>
    <input type="hidden" name="type" value="<?= $type; ?>">
    <input type="hidden" name="id" value="<?= $id; ?>">
    <input type="hidden" name="action" value="share">
    <input type="submit" name="submit" value="Submit" class="button">
</form>
