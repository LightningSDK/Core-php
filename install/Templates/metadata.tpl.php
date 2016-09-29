<?php
use Lightning\Model\URL;
use Lightning\Tools\Configuration;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
?>
<link rel="icon" href="/favicon.png" type="image/x-icon">
<?php if (!empty($meta)): ?>
    <?php if (!empty($meta['title'])): ?>
        <title><?= Scrub::toHTML($meta['title']); ?></title>
        <meta property="og:title" content="<?= Scrub::toHTML($meta['title']); ?>" />
        <meta name="twitter:title" content="<?= Scrub::toHTML($meta['title']); ?>">
    <?php endif; ?>
    <meta name="robots" content="ALL, INDEX, FOLLOW" />
    <meta name="copyright" content="http://LightningSDK.net Copyright (c) 2016">
    <meta name="og:url" content="<?= Scrub::toHTML(!empty($meta['url']) ? $meta['url'] : Request::getURL()); ?>" />
    <?php if ($appid = Configuration::get('social.facebook.appid')): ?>
        <meta name="og:app_id" content="<?= Scrub::toHTML($appid); ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['keywords'])): ?>
        <meta name="keywords" content="<?= Scrub::toHTML($meta['keywords']); ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['description'])): ?>
        <meta name="description" content="<?= Scrub::toHTML($meta['description']); ?>">
        <meta property="og:description" content="<?= Scrub::toHTML($meta['description']); ?>">
        <meta name="twitter:description" content="<?= Scrub::toHTML($meta['description']); ?>">
    <?php endif; ?>
    <?php if (!empty($meta['author'])): ?>
        <meta name="author" content="<?= Scrub::toHTML($meta['author']); ?>">
    <?php endif; ?>
    <?php if (!empty($meta['twitter_site'])): ?>
        <meta name="twitter:site" content="@<?= $meta['twitter_site']; ?>">
    <?php endif; ?>
    <?php if (!empty($meta['twitter_creator'])): ?>
        <meta name="twitter:creator" content="@<?= $meta['twitter_creator']; ?>">
    <?php endif; ?>
    <?php if (!empty($meta['image'])): ?>
        <meta property="og:image" content="<?= Scrub::toHTML(URL::getAbsolute($meta['image'])); ?>" />
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="<?= Scrub::toHTML(URL::getAbsolute($meta['image'])); ?>">
    <?php endif; ?>
<?php endif; ?>
