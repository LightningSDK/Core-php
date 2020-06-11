<div class="panel blog_history">

    <?php if (!empty($blog)): ?>
        <?php if (\lightningsdk\core\Model\BlogPost::getRecent()): ?>
            <h3>Recent Posts</h3>
            <?= $blog->renderRecentList() ?>
        <?php endif; ?>

        <?php if (\lightningsdk\core\Model\BlogPost::getAllCategories()): ?>
            <h3>Blog Categories</h3>
            <?= $blog->renderCategoriesList() ?>
        <?php endif; ?>
    <?php endif; ?>

</div>
