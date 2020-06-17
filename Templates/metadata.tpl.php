<?php
use lightningsdk\core\Model\URL;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Scrub;
?>
<link rel="icon" href="<?= Configuration::get('favicon') ?: '/favicon.png'; ?>" type="image/x-icon">
<?php if (!empty($meta)): ?>
    <?php if (!empty($meta['title'])): ?>
        <title><?= Scrub::text($meta['title']); ?></title>
        <meta property="og:title" content="<?= Scrub::text($meta['title']); ?>" />
        <meta name="twitter:title" content="<?= Scrub::text($meta['title']); ?>" />
    <?php endif; ?>
    <?php if (!empty($mets['facebook_app_id'])): ?>
        <meta name="fb:app_id" content="<?= $mets['facebook_app_id']; ?>" />
    <?php endif; ?>
    <meta name="robots" content="ALL, INDEX, FOLLOW" />
    <?php if (!empty($meta['keywords'])): ?>
        <meta name="copyright" content="<?php Scrub::text($meta['copyright']); ?>" />
    <?php endif; ?>
    <meta name="og:url" content="<?= Scrub::text(!empty($meta['url']) ? $meta['url'] : Request::getURL()); ?>" />
    <?php if ($appid = Configuration::get('social.facebook.appid')): ?>
        <meta name="og:app_id" content="<?= Scrub::text($appid); ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['keywords'])): ?>
        <meta name="keywords" content="<?= Scrub::text($meta['keywords']); ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['description'])): ?>
        <meta name="description" content="<?= Scrub::text($meta['description']); ?>" />
        <meta property="og:description" content="<?= Scrub::text($meta['description']); ?>" />
        <meta name="twitter:description" content="<?= Scrub::text($meta['description']); ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['author'])): ?>
        <meta name="author" content="<?= Scrub::text($meta['author']); ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['twitter_site'])): ?>
        <meta name="twitter:site" content="@<?= $meta['twitter_site']; ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['twitter_creator'])): ?>
        <meta name="twitter:creator" content="@<?= $meta['twitter_creator']; ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['image'])): ?>
        <meta property="og:image" content="<?= Scrub::text(URL::getAbsolute($meta['image'])); ?>" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:image" content="<?= Scrub::text(URL::getAbsolute($meta['image'])); ?>" />
    <?php endif; ?>
    <?php if (!empty($meta['google_webmaster_verification'])): ?>
        <meta name="google-site-verification" content="<?= $meta['google_webmaster_verification']; ?>" />
    <?php endif; ?>
<?php endif; ?>
