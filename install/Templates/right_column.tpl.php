<div class="panel blog_history">

    <?php if (!empty($blog)): ?>
        <h3>Recent Posts</h3>
        <?= $blog->renderRecentList() ?>

        <h3>Blog Categories</h3>
        <?= $blog->renderCategoriesList() ?>
    <?php endif; ?>

</div>
