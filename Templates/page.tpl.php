<div id='content'>
    <div id='inner-content' class="content_panel padding">
        <?php

        use Lightning\Tools\Configuration;
        use Lightning\View\SocialLinks;

        if (!empty($editable)): ?>
            <div class="page-edit-links">
                <a href='/admin/pages?action=edit&return-to-view=true&id=<?= $full_page['page_id']; ?>' class="button medium">Edit This Page</a>
            </div>
        <?php endif; ?>

        <?= $full_page['body_rendered']; ?>

        <?php if (!empty($full_page['error'])): ?>
            <div class="social-share"><?= SocialLinks::render(Configuration::get('web_root') . '/' . (!empty($full_page['url']) ? $full_page['url'] . '.html' : '')); ?></div>
        <?php endif; ?>
    </div>
</div>
