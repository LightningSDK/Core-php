<div id='content'>
    <div id='inner-content' class="content_panel padding">
        <?php

        use Lightning\Tools\Configuration;
        use Lightning\View\SocialLinks;

        if (!empty($editable)): ?>
            <div class="page-edit-links">
                <?php if (!empty($full_page['page_id'])): ?>
                    <a href='/admin/pages?action=edit&action-after=view&id=<?= $full_page['page_id']; ?>' class="button medium">Edit This Page</a>
                <?php else: ?>
                    <a href='/admin/pages?action=new&action-after=view&url=<?= \Lightning\Tools\Scrub::toHTML($full_page['url']); ?>' class="button medium">Create This Page</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?= $full_page['body_rendered']; ?>

        <?php if (!empty($full_page['error']) && !empty($share)): ?>
            <div class="social-share"><?= SocialLinks::render(Configuration::get('web_root') . '/' . (!empty($full_page['url']) ? $full_page['url'] : '')); ?></div>
        <?php endif; ?>
    </div>
</div>
