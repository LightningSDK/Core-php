<div class="blog-container">
    <?php
    use Lightning\Tools\ClientUser;
    use Lightning\Tools\Configuration;
    use Lightning\View\SocialLinks;

    $user = ClientUser::getInstance();

    if (count($blog->posts) > 0): ?>

        <?= $blog->pagination(); ?>
        <?php foreach ($blog->posts as $post): ?>
            <div class="article">
                <?php if (!$blog->isList()): ?>
                    <?php if (!empty($post['header_image']) && empty($post['header_from_source'])): ?>
                        <div class="blog-header-image" style="background-image:url(<?=$post['header_image'];?>);"></div>
                    <?php endif; ?>
                    <h1><?=$post['title'];?></h1>
                <?php else: ?>
                    <?php if (!empty($post['header_image'])): ?>
                        <a href='/<?=$post['url'];?>.htm'><div class="blog-header-image" style="background-image:url(<?=$post['header_image'];?>);"></div></a>
                    <?php endif; ?>
                    <h2><a href='/<?=$post['url'];?>.htm'><?=$post['title'];?></a></h2>
                <?php endif; ?>
                <div class="date">
                    <?= date('F j, Y', $post['time']) ?></div>
                <ul class="tags">
                    <?php if (!empty($post['author_name']) && !empty($post['author_url'])): ?>
                        <li>
                            <a href="/blog/author/<?=$post['author_url']?>"><?=$post['author_name']?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($post['categories'])):
                        foreach ($post['categories'] as $cat): ?>
                            <li>
                                <a href="/blog/category/<?= $blog->getCatURL($cat); ?>"><?= $cat; ?></a>
                            </li>
                        <?php endforeach;
                    endif; ?>
                </ul>
                <div class="body-wrapper">
                    <div class="body" <?php if (!$blog->isList()):?>id='blog_body'<?php endif; ?>>
                        <?php if ($user->isAdmin()): ?><a href="/admin/blog/edit?return=view&id=<?=$post['blog_id'];?>" class="button small">Edit this Post</a><br /><?php endif; ?>
                        <?php if ($blog->isList()): ?>
                            <?=$blog->shortBody($post['body'], 500)?>
                            <br><a href='/<?=$post['url']?>.htm' class="more">read more</a>
                        <?php else: ?>
                            <?=$blog->body($post['body'])?>
                        <?php endif; ?>
                    </div>
                    <?= SocialLinks::render(Configuration::get('web_root') . '/' . $post['url'] . '.htm'); ?>
                </div>
                <?php if (!$blog->isList()): ?>
                    <?php if(!empty($post['author_image'])): ?>
                        <div class="author">
                            <img src="/img/blog/<?= $post['author_image']; ?>">
                            <div class="info">
                                <h4><?= $post['author_name']; ?></h4>
                                <p><?= $post['author_description']; ?></p>
                                <a href="/blog/author/<?= $post['author_url']; ?>">ALL FROM <?= $post['author_name']; ?> <i class="fa fa-angle-right"></i></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?= \Lightning\View\Facebook\Comments::render(); ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?=$blog->pagination()?>
    <?php elseif (!empty($page_section) && $page_section == "blog"): ?>
        The page you are looking for does not exist.
    <?php elseif (!empty($page_section) && $page_section == "blog_list"): ?>
        Nothing Found.
    <?php endif; ?>
</div>
