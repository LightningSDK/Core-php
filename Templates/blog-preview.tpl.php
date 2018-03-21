<div class="blog-preview">
    <div class="preview" style="background-image:url(<?= $blog->getHeaderImage(); ?>)">
        <div class="headline"><a href="<?= $blog->getLink(); ?>"><?= $blog->title; ?></a></div>
    </div>
    <div class="body short"><?= $blog->getShortBody(); ?> <a href="<?= $blog->getLink(); ?>" class="more">read more</a></div>
</div>
