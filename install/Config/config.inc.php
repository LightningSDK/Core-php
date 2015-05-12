<?php

$conf = array(
    'database' => 'mysql:user=user;password=pass;host=localhost;dbname=db',
    'user' => array(
        'cookie_domain' => '',
        // Generate a new key by going to the Lightning directory and running
        // ./lightning security generate-aes-key
        // **** THIS KEY IS INCLUDED WITH THE DISTRIBUTION AND IS NOT SECURE ****
        'key' => 'o2sdxfGwHn2YGcQ3Xh2Z8p5y/BP0dHdbtdmU3ATdMwE=',
        'multiple_devices' => false,
        'min_password_length' => 6,
    ),
    'session' => array(
        'single_ip' => true,
    ),
    'site' => array(
        'mail_from' => 'donotreply@Website.com',
        'mail_from_name' => 'My Mailer',
        'name' => 'My Website',
        'domain' => 'Website.com',
        'email_domain' => 'www.Website.com',
        'log' => '../logs/web.log',
    ),
    'contact' => array(
        'subject' => 'Message from Website.com',
        'auto_responder' => 0, // Change to this a message_id of an auto respond.
        'spam_test' => false, // Whether to tell them to immediately look for the auto respond in their junk folder.
        'to' => array('youremail@gmail.com'),
        'cc' => array(),
        'bcc' => array(),
    ),
    'mailer' => array(
        'test' => array(),
        'spam_test' => array(),
        'spam_test_from' => null,
        'mail_from' => null,
        'mail_from_name' => null,
        'bounce_address' => null,
        'default_template' => null,
        'default_list' => null,
        'mail_template' => null,
    ),
    'tracker' => array(
        'allow_unencrypted' => true,
        // Generate a new key by going to the Lightning directory and running
        // ./lightning security generate-aes-key
        // **** THIS KEY IS INCLUDED WITH THE DISTRIBUTION AND IS NOT SECURE ****
        'key' => '0Xx+v7xGDanBpTgDoIqwlA==:JPJdzm5ifvePYztVj1ICrQ==',
    ),
    'ckfinder' => array(
        'content' => '/images/'
    ),
    'meta_data' => array(
        'title' => '',
        'keywords' => '',
        'description' => '.',
    ),
    'google_analytics_id' => '',
    'use_mobile_site' => true,
    'recaptcha' => array(
        'public' => '',
        'private' => '',
    ),
    'web_root' => 'http://www.Website.com',
    'daemon' => array(
        'max_threads' => 5,
        'log' => '../logs/daemon.log',
    ),
    'cli' => array(
        'log' => '../logs/cli.log',
    ),
    'jobs' => array(
        'session_cleanup' => array(
            'class' => 'Lightning\\Jobs\\UserCleanup',
            'offset' => 7200, // 2 am server time
            'interval' => 86400,
            'max_threads' => 1,
        ),
    ),
    'routes' => array(
        'dynamic' => array(
            '.*\.html' => 'Lightning\\Pages\\Page',
            '^blog(/.*)?$' => 'Lightning\\Pages\\Blog',
            '.*\.htm' => 'Lightning\\Pages\\Blog',
        ),
        'static' => array(
            '' => 'Lightning\\Pages\\Page',
            'user' => 'Lightning\\Pages\\User',
            'contact' => 'Lightning\\Pages\\Contact',
            'page' => 'Lightning\\Pages\\Page',
            'message' => 'Lightning\\Pages\\Message',
            'blog/edit' => 'Lightning\\Pages\\BlogTable',
            'blog/categories' => 'Lightning\\Pages\\BlagCategories',
            'admin/mailing/lists' => 'Lightning\\Pages\\Mailing\\Lists',
            'admin/mailing/messages' => 'Lightning\\Pages\\Mailing\\Messages',
            'admin/mailing/send' => 'Lightning\\Pages\\Mailing\\Send',
            'admin/mailing/stats' => 'Lightning\\Pages\\Mailing\\Stats',
            'admin/mailing/templates' => 'Lightning\\Pages\\Mailing\\Templates',
            'admin/tracker' => 'Lightning\\Pages\\AdminTracker',
            'admin/pages' => 'Lightning\\Pages\\Admin\\Pages',
            'admin/users' => 'Lightning\\Pages\\Admin\\Users',
            'admin/cms' => 'Lightning\\Pages\\Admin\\CMS',
            'sitemap' => 'Lightning\\Pages\\Sitemap',
            'track' => 'Lightning\\Pages\\Track',
            'landing' => 'Lightning\\Pages\\OptIn',
            'optin' => 'Lightning\\Pages\\OptIn',
            'profile' => 'Lightning\\Pages\\Profile',
        ),
        'cli_only' => array(
            'bounce' => 'Lightning\\CLI\\BouncedEmail',
            'test' => 'Source\\CLI\\Test',
        )
    ),
    'language' => 'en_us',
);
