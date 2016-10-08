<div class="blog-container">
    <?php
    use Lightning\Tools\ClientUser;
    use Lightning\View\SocialLinks;
    use Lightning\Model\BlogPost;

    $user = ClientUser::getInstance();

    if (count($blog->posts) > 0): ?>

        <?= $blog->pagination(); ?>
        <?php foreach ($blog->posts as $post): ?>
            <?php $post = new BlogPost($post); ?>
            <div class="article">
                <?php if (!$blog->isList()): ?>
                    <?php if ($image = $post->getTrueHeaderImage()): ?>
                        <div class="blog-header-image" style="background-image:url(<?=$image;?>);"></div>
                    <?php endif; ?>
                    <h1><?=$post->title;?></h1>
                <?php else: ?>
                    <?php if ($image = $post->getHeaderImage()): ?>
                        <a href="<?=$post->getLink();?>"><div class="blog-header-image" style="background-image:url(<?=$image;?>);"></div></a>
                    <?php endif; ?>
                    <h2><a href='<?=$post->getLink();?>'><?=$post->title;?></a></h2>
                <?php endif; ?>
                <div class="date">
                    <?= date('F j, Y', $post->time) ?></div>
                <ul class="tags">
                    <?php if ($author = $post->getAuthorName() && $author_url = $post->getAuthorLink()): ?>
                        <li>
                            <a href="<?= $post->getAuthorLink(); ?>"><?=$post->author_name?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (!empty($post->categories)): ?>
                        <?= $post->renderCategoryList(); ?>
                    <?php endif; ?>
                </ul>
                <div class="body-wrapper">
                    <div class="body" <?php if (!$blog->isList()):?>id='blog_body'<?php endif; ?>>
                        <?php if ($user->isAdmin()): ?><a href="/admin/blog/edit?return=view&id=<?=$post->id;?>" class="button small">Edit this Post</a><br /><?php endif; ?>
                        <?php if ($blog->isList()): ?>
                            <?=$post->getShortBody(500);?>
                            <br><a href="<?=$post->getLink()?>" class="more">read more</a>
                        <?php else: ?>
                            <?=$post->getBody()?>
                        <?php endif; ?>
                    </div>
                    <?php if (!$blog->isList() && !empty($post->author_image)): ?>
                        <div class="author row panel">
                            <h3>About the author:</h3>
                            <div class="small-12 medium-4 column">
                                <img src="<?= $post->author_image; ?>">
                            </div>
                            <div class="info small-12 medium-8 column">
                                <h4><?= $post->author_name; ?></h4>
                                <p><?= $post->author_description; ?></p>
                                <a href="<?= $post->getAuthorLink(); ?>">See all posts from <?= $post->author_name; ?> <i class="fa fa-angle-right"></i></a>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?= SocialLinks::render($post->getURL()); ?>
                    <?php if (!$blog->isList()): ?>
                        <?= \Lightning\View\Facebook\Comments::render(); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?=$blog->pagination()?>
    <?php elseif (!empty($page_section) && $page_section == "blog"): ?>
        The page you are looking for does not exist.
    <?php elseif (!empty($page_section) && $page_section == "blog_list"): ?>
        Nothing Found.
    <?php endif; ?>
</div>
