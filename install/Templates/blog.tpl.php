<?
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;

$user = ClientUser::getInstance();

if ($user->isAdmin()): ?>
    <a href='/admin/blog/comments'>Approve Comments</a><br />
    <a href='/table.php?table=categories' target="_blank">Edit categories</a><br /><br />
<? endif; ?>

<? if (count($blog->posts) > 0): ?>
    <?=$blog->pagination()?>
    <? foreach ($blog->posts as $post): ?>
        <? if ( count($blog->posts) == 1): ?>
            <h1><?=$post['title'];?></h1>
            <?= $this->build('social_links'); ?>
        <? else: ?>
            <h2><a href='/<?=$post['url'];?>.htm'><?=$post['title'];?></a></h2>
        <? endif; ?>
        <h4 class="blog_header_date">Posted on: <?=date('F j, Y \a\t g:iA',$post['time']);?></h4>
        <div class="blog_body" <? if ( count($blog->posts) == 1):?>id='blog_body'<? endif; ?>>
            <? if ($user->isAdmin()): ?><a href='/blog/edit?return=view&id=<?=$post['blog_id'];?>'>Edit this Post</a><br /><? endif; ?>
            <?=$blog->body($post['body'])?><? if ( count($blog->posts) > 1):?> <a href='/<?=$post['url']?>.htm'>read more...</a><? endif; ?>
        </div>
        <br />
        <?= $this->build('social_links'); ?>
        <br />

        <? if (count($blog->posts) == 1): ?>
            <div class="fb-comments" data-href="http://<?=Configuration::get('site.domain')?>/<?=$blog->posts[0]['url']?>.htm" data-width="560" data-num-posts="10"></div>

        <? endif; ?>

        <div data-src="<?=$post['url']?>" class="OUTBRAIN" ></div>
        <script type="text/javascript">(function(){window.OB_platformType=8;window.OB_langJS="http://widgets.outbrain.com/lang_en.js";window.OBITm="1394419150171";window.OB_recMode="brn_strip";var ob=document.createElement("script");ob.type="text/javascript";ob.async=true;ob.src="http"+("https:"===document.location.protocol?"s":"")+"://widgets.outbrain.com/outbrainLT.js";var h=document.getElementsByTagName("script")[0];h.parentNode.insertBefore(ob,h);})();</script>

    <? endforeach; ?>
    <?=$blog->pagination()?>
<? elseif ($page_section == "blog"): ?>
    The page you are looking for does not exist.
<? elseif ($page_section == "blog_list"): ?>
    Nothing Found.
<? endif; ?>
