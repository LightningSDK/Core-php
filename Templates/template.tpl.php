<!doctype html>
<?
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;
use Lightning\View\JS;
use Lightning\View\CSS;
?>
<!--[if IE 9]><html class="lt-ie10" lang="en" > <![endif]-->
<html class="no-js" lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?=Configuration::get('page_title');?></title>
    <link rel="icon" href="img/icons/favicon.ico" type="image/x-icon">
    <!-- For third-generation iPad with high-resolution Retina display: -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="img/icons/apple-touch-icon-144x144-precomposed.png">
    <!-- For iPhone with high-resolution Retina display: -->
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="img/icons/apple-touch-icon-114x114-precomposed.png">
    <!-- For first- and second-generation iPad: -->
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="img/icons/apple-touch-icon-72x72-precomposed.png">
    <!-- For non-Retina iPhone, iPod Touch, and Android 2.1+ devices: -->
    <link rel="apple-touch-icon-precomposed" href="img/icons/apple-touch-icon-precomposed.png">

    <meta name="keywords" content="<?=Configuration::get('page_keywords');?>" />
    <meta name="robots" content="ALL, INDEX, FOLLOW" />
    <meta name="description" content="<?=Configuration::get('page_description');?>">
    <meta name="author" content="Dan B">
    <meta name="copyright" content="LightningSDK.com Copyright (c) 2014">
    <?= JS::render(); ?><?= CSS::render(); ?>
</head>
<body class="antialiased hide-extras">
<div class="marketing off-canvas-wrap" data-offcanvas>
    <div class="inner-wrap">

        <div class="row">
            <div class="small-12">
                <h1>Welcome!</h1>
            </div>
            <div id="nav_wrapper" class="small-12">
                <div class="slider single-item">
                    <div><img src="/images/your_voice.jpg" width="1000" height="268" alt="slide1" /></div>
                    <div><img src="/images/slide1.jpg" width="1000" height="268" alt="slide1" /></div>
                    <div><a href="/report"><img src="/images/report_it_now.jpg" width="1000" height="268" alt="slide1" /></a></div>
                    <div><img src="/images/slide2.jpg"  width="1000" height="268" alt="slide2" /></div>
                </div>
            </div>
        </div>
        <div class="row">
            <nav class="top-bar" data-topbar>
                <section class="top-bar-section">
                    <ul class="title-area">
                        <li class="name">
                            <h1><a href="/">Your new site</a></h1>
                        </li>
                        <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
                        <li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
                    </ul>
                    <section class="top-bar-section">
                        <ul class="right">
                            <li class="active"><a href="/">Home</a></li>
                            <li><a href="/blog">Blog</a></li>
                            <li><a href="/search_reports">Search Reports</a></li>
                            <li><a href="/contact">Contact</a></li>
                            <li><a href="/report">File A Report</a></li>
                            <li><a href="/lawyers">Find a Lawyer</a></li>
                            <li><a href="/organizations">Other Organizations</a></li>
                        </ul>
                    </section>
                </section>
            </nav>
            <? if (ClientUser::getInstance()->isAdmin()): ?>
                <nav class="top-bar" data-topbar>
                    <section class="top-bar-section">
                        <ul class="title-area">
                            <li class="name">
                                <h1><a href="/">Admin Menu</a></h1>
                            </li>
                            <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
                            <li class="toggle-topbar menu-icon"><a href="#"><span>Admin Menu</span></a></li>
                        </ul>
                        <section class="top-bar-section">
                            <ul class="right">
                                <li><a href="/blog/edit">Blog</a></li>
                                <li><a href="/blog/comments">Blog Comments</a></li>
                                <li><a href="/users">Users</a></li>
                            </ul>
                        </section>
                    </section>
                </nav>
            <? endif; ?>
        </div>
        <div class="row" style="background-color: #79bdfd; padding: 10px;">
            <h2>We are updating our site!</h2>
            <p>We have been working diligently on updating the site to give everyone a better user experience. We want your feedback! If you have any comments or suggestions that would make the site easier to use, please <a href="/contact">contact us here</a>.</p>
        </div>

        <section role="main" class="scroll-container">
            <div class="row">
                <? if (empty($full_width)): ?>
                    <div class="medium-8 columns">
                        <? if (!empty($page_header)): ?>
                            <h1 id="page_header"><?=$page_header?></h1>
                        <?
                        endif;
                        $errors = Messenger::getErrors();
                        if (count($errors) > 0): ?>
                            <div class="messenger error">
                                <ul><? foreach ($errors as $error): ?><li><?=$error?></li><? endforeach; ?></ul>
                            </div>
                        <? endif;
                        $messages = Messenger::getMessages();
                        if (count($messages) > 0): ?>
                            <div class="messenger message">
                                <ul><? foreach ($messages as $message): ?><li><?=$message?></li><? endforeach; ?></ul>
                            </div>
                        <?
                        endif;
                        if (!empty($content)) :
                            $this->_include($content);
                        endif; ?>
                    </div>
                    <div class="small-12 medium-4 columns">
                        <? $this->_include('right_column'); ?>
                    </div>
                <? else: ?>
                    <div class="large-12 columns">
                        <? if (!empty($page_header)): ?>
                            <h1 id="page_header"><?=$page_header?></h1>
                        <?
                        endif;
                        $errors = Messenger::getErrors();
                        if (count($errors) > 0): ?>
                            <div class="messenger error">
                                <ul><? foreach ($errors as $error): ?><li><?=$error?></li><? endforeach; ?></ul>
                            </div>
                        <? endif;
                        $messages = Messenger::getMessages();
                        if (count($messages) > 0): ?>
                            <div class="messenger message">
                                <ul><? foreach ($messages as $message): ?><li><?=$message?></li><? endforeach; ?></ul>
                            </div>
                        <?
                        endif;
                        if (!empty($content)) :
                            $this->_include($content);
                        endif; ?>
                    </div>
                <? endif; ?>
            </div>
            <pre>
            <?
                $database = Database::getInstance();
                print_r($database->get_queries());
                print_r($database->time_report());
            ?>
            </pre>
        </section>
    </div>
</div>
<?= JS::render(); ?><?= CSS::render(); ?>
</body>
</html>
