<?php
use Lightning\Tools\ClientUser;
use Lightning\Tools\Scrub;
use Lightning\Tools\Configuration;
use Lightning\Model\Blog;

$user = ClientUser::getInstance();

if (count($blog->posts) > 0): ?>

    <?=$blog->pagination()?>
    <? foreach ($blog->posts as $post): ?>
        <div class="IndiArticle">
            <? if (!$blog->isList()): ?>
                <?php if ($image = $blog->getImage($post)): ?>
                    <div class="blog-header-image" style="background-image:url(<?=$image;?>);"></div>
                <?php endif; ?>
                <h1><?=$post['title'];?></h1>
            <? else: ?>
                <?php if ($image = $blog->getImage($post)): ?>
                    <a href='/<?=$post['url'];?>.htm'><div class="blog-header-image" style="background-image:url(<?=$image;?>);"></div></a>
                <?php endif; ?>
                <h2><a href='/<?=$post['url'];?>.htm'><?=$post['title'];?></a></h2>
            <? endif; ?>
            <div class="blog-date">
                <?= date('F j, Y', $post['time']) ?></div>
            <div class="TextBlock">
                <ul class="blog-meta">
                    <? if (!empty($post['author_name']) && !empty($post['author_url'])): ?>
                        <li>
                            <a href="/blog/author/<?=$post['author_url']?>"><?=$post['author_name']?></a>
                        </li>
                    <? endif; ?>
                    <? if (!empty($post['categories'])):
                        foreach ($post['categories'] as $cat): ?>
                            <li>
                                <a href="/blog/category/<?= Scrub::toURL($cat); ?>"><?= $cat; ?></a>
                            </li>
                        <? endforeach;
                    endif; ?>
                </ul>
                <div class="blog_body" <? if (!$blog->isList()):?>id='blog_body'<? endif; ?>>
                    <? if ($user->isAdmin()): ?><a href="/blog/edit?return=view&id=<?=$post['blog_id'];?>" class="button">Edit this Post</a><br /><? endif; ?>
                    <? if ($blog->isList()): ?>
                        <?=$blog->shortBody($post['body'], 500)?>
                        <br><a href='/<?=$post['url']?>.htm' class="blkMore">read more</a>
                    <? else: ?>
                        <?=$blog->body($post['body'])?>
                    <? endif; ?>
                </div>
                <? if (!$blog->isList()):
                    $this->build('social_links');
                endif;
                ?>
            </div>
            <? if (!$blog->isList()): ?>
                <? if(!empty($post['author_image'])): ?>
                <div class="author">
                    <img src="/img/blog/<?= $post['author_image']; ?>">
                    <div class="info">
                        <h4><?= $post['author_name']; ?></h4>
                        <p><?= $post['author_description']; ?></p>
                        <a href="/blog/author/<?= $post['author_url']; ?>">ALL FROM <?= $post['author_name']; ?> <i class="fa fa-angle-right"></i></a>
                    </div>
                </div>
            <? endif; ?>
                <div id="fb-root"></div>
                <script>(function(d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0];
                        if (d.getElementById(id)) return;
                        js = d.createElement(s); js.id = id;
                        js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
                        fjs.parentNode.insertBefore(js, fjs);
                    }(document, 'script', 'facebook-jssdk'));</script>
                <div class="fb-comments" data-numposts="5" data-width="100%" data-colorscheme="light"></div>

            <? endif; ?>

            <div data-src="<?=$post['url']?>" class="OUTBRAIN" ></div>
            <script type="text/javascript">(function(){window.OB_platformType=8;window.OB_langJS="http://widgets.outbrain.com/lang_en.js";window.OBITm="1394419150171";window.OB_recMode="brn_strip";var ob=document.createElement("script");ob.type="text/javascript";ob.async=true;ob.src="http"+("https:"===document.location.protocol?"s":"")+"://widgets.outbrain.com/outbrainLT.js";var h=document.getElementsByTagName("script")[0];h.parentNode.insertBefore(ob,h);})();</script>
        </div>
    <? endforeach; ?>
    <?=$blog->pagination()?>
<? elseif ($page_section == "blog"): ?>
    The page you are looking for does not exist.
<? elseif ($page_section == "blog_list"): ?>
    Nothing Found.
<? endif; ?>
