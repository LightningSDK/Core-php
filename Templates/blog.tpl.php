<? if ( $user->details['type'] >= 5): ?><a href='/admin/blog/comments'>Approve Comments</a><br /><a href='/table.php?table=categories' target="_blank">Edit categories</a><br /><br /><? endif; ?>

<? if (!empty($blog_table)): ?>
    <?=$blog_table->render_table();?>
<? elseif ( count($blog->posts) > 0): ?>
    <?=$blog->pagination()?>
    <? foreach ($blog->posts as $post): ?>
        <? if ( count($blog->posts) == 1): ?>
            <h1><?=$post['title'];?></h1>
            <?= $this->_include('social_links'); ?>
        <? else: ?>
            <h2><a href='/<?=$post['url'];?>.htm'><?=$post['title'];?></a></h2>
        <? endif; ?>
        <h4 class="blog_header_date">Posted on: <?=date('F j, Y \a\t g:iA',$post['time']);?></h4>
        <? if ( count($blog->posts) == 1 && $user->details['type'] >= 5): ?>
            <div style="display:none;" id='blog_edit'>
                <form action="/blog/edit" method="post">
                    <input type="hidden" name="blog_id" value="<?=$post['blog_id']?>" />
                    <input type="hidden" name="action" value="update_blog" />
                    Title: <input type="text" name="title" value="<?=$post['title']?>" /><br />
                    <?=$blog_table->render_table()?>
                    <input type="submit" name="submit" value="Save Changes" />
                </form>
            </div>
        <? endif; ?>
        <div class="blog_body" <? if ( count($blog->posts) == 1):?>id='blog_body'<? endif; ?>>
            <? if ( $user->details['type'] >= 5): ?><a href='/blog/edit?id=<?=$post['blog_id'];?>'>Edit this Post</a><br /><? endif; ?>
            <?=$blog->body($post['body'])?><? if ( count($blog->posts) > 1):?> <a href='/<?=$post['url']?>.htm'>read more...</a><? endif; ?>
        </div>
        <br />
        <?= $this->_include('social_links'); ?>
        <br />

        <? if (count($blog->posts) == 1): ?>
            <script type="text/javascript"><!--
                google_ad_client = "ca-pub-9935477002839455";
                /* AA big square */
                google_ad_slot = "3389299937";
                google_ad_width = 300;
                google_ad_height = 250;
                //-->
            </script>
            <script type="text/javascript"
                    src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
            </script>
            <br>
            <div class="fb-comments" data-href="http://accountableauthority.com/<?=$blog->posts[0]['url']?>.htm" data-width="560" data-num-posts="10"></div>

        <? endif; ?>

        <div data-src="<?=$post['url']?>" class="OUTBRAIN" ></div>
        <script type="text/javascript">(function(){window.OB_platformType=8;window.OB_langJS="http://widgets.outbrain.com/lang_en.js";window.OBITm="1394419150171";window.OB_recMode="brn_strip";var ob=document.createElement("script");ob.type="text/javascript";ob.async=true;ob.src="http"+("https:"===document.location.protocol?"s":"")+"://widgets.outbrain.com/outbrainLT.js";var h=document.getElementsByTagName("script")[0];h.parentNode.insertBefore(ob,h);})();</script>

        <? if (count($blog->posts) == 1 || !empty($post['comments'])):?>
            <h2>Comments:</h2>
            <? if (count($blog->posts) == 1): ?>
                <div id='new_comment_box'>
                    <table border="0">
                        <tr><td>Name:</td><td><input type="text" name="blog_comment_name" id="blog_comment_name" /></td></tr>
                        <tr><td>Email:</td><td><input type="text" name="blog_comment_email" id="blog_comment_email" /></td></tr>
                        <tr><td>Website:</td><td><input type="text" name="blog_comment_web" id="blog_comment_web" /></td></tr>
                        <tr><td colspan="2"><textarea id='blog_new_comment' name="blog_new_comment"></textarea></td></tr>
                        <tr><td colspan="2" align="center">
                                <input type="button" name="blog_submit_comment" id="blog_submit_comment" value="comment" onclick="submit_comment(<?=$post['blog_id']?>)" /></td></tr>
                    </table>
                </div>
            <? endif; ?>
            <div class="blog_comment_container" id='blog_comment_container'>
                <? foreach ($post['comments'] as $comment): ?>
                    <div class="blog_comment" id='blog_comment_<?=$comment['blog_comment_id']?>'>
                        <? if ( $user->details['type'] >= 5): ?>
                            <span class="delete_comment" onclick="delete_blog_comment(<?=$$comment['blog_comment_id']?>);">X</span>
                            <? if ( $comment['approved'] == 0): ?>
                                <span class="approve_comment" onclick="approve_blog_comment(<?=$comment['blog_comment_id']?>);">Approve</span>
                            <? endif; ?>
                        <? endif; ?>
                        <span class="blog_comment_body"><?=$comment['comment']?></span><br />
                        <span class="blog_comment_name">By <?=$comment['name']?></span>
                        <span class="blog_comment_date">on <?=date('F j, Y \a\t g:iA', $comment['time'])?></span>
                    </div>
                <? endforeach; ?>
            </div>
            <div class="blog_comment" id='blog_comment_blank' style="display:none;">
                <span class="blog_comment_body"></span><br />
                <span class="blog_comment_name"></span> <span class="blog_comment_date"></span>
            </div>
        <? endif; ?>

    <? endforeach; ?>
    <?=$blog->pagination()?>
<? elseif ($page_section == "blog"): ?>
    The page you are looking for does not exist.
<? elseif ($page_section == "blog_list"): ?>
    Nothing Found.
<? endif; ?>
