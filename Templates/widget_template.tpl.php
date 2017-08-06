<!doctype html>
<!--[if IE 9]><html class="lt-ie10" lang="en" > <![endif]-->
<html class="no-js" lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $this->build('metadata'); ?>
    <?= $this->renderHeader(); ?>
</head>
<body class="antialiased hide-extras <?= !empty($fullscreen) ? 'fullscreen' : 'standard'; ?>">
<div id="widget-body">
    <?php
    if (!empty($content)) { $this->build($content); }
    ?>
</div>
<?= $this->renderFooter(); ?>
</body>
</html>
