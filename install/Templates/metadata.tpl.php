<?php
use Lightning\Tools\Scrub;
?>
<link rel="icon" href="/favicon.png" type="image/x-icon">
<title><?= Scrub::toHTML($meta['title']); ?></title>
<meta name="keywords" content="<?= Scrub::toHTML($meta['keywords']); ?>" />
<meta name="robots" content="ALL, INDEX, FOLLOW" />
<meta name="description" content="<?= Scrub::toHTML($meta['description']); ?>">
<meta name="author" content="<?= Scrub::toHTML($meta['author']); ?>">
<meta name="copyright" content="http://TopLocal.Marketing Copyright (c) 2016">
<?php if (!empty($meta['image'])): ?>
    <meta property="og:image" content="<?= Scrub::toHTML($meta['image']); ?>" />
<?php endif; ?>
<meta property="og:title" content="<?= Scrub::toHTML($meta['title']); ?>" />
<meta property="og:description" content="<?= Scrub::toHTML($meta['description']); ?>">
