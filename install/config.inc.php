<?php

$conf = array(
    'database' => 'mysql:user=user;password=pass;host=localhost;dbname=db',
    'user' => array(
        'cookie_domain' => '',
    ),
    'site' => array(
        'mail_from' => 'donotreply@Website.com',
        'mail_from_name' => 'My Mailer',
        'name' => 'My Website',
        'domain' => 'Website.com',
        'email_domain' => 'www.Website.com',
    ),
    'contact' => array(
        'subject' => 'Message from Website.com',
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
    ),
    'tracker' => array(
        'allow_unencrypted' => true,
        // Generate a new key by going to the Lightning directory and running
        // ./lightning security generate-aes-key
        // **** THIS KEY IS INCLUDED WITH THE DISTRIBUTION AND IS NOT SECURE ****
        'key' => '0Xx+v7xGDanBpTgDoIqwlA==:JPJdzm5ifvePYztVj1ICrQ==',
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
    'web_root' => '',
    'routes' => array(
        'dynamic' => array(
            '^agency/.*' => 'Source\\Pages\\Agency',
            '^officer/.*' => 'Source\\Pages\\Officer',
            '^complaint/.*' => 'Source\\Pages\\Complaint',
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
            'admin/mailing/stats' => 'Lightning\\Pages\\AdminMailingStats',
            'admin/tracker' => 'Lightning\\Pages\\AdminTracker',
            'sitemap' => 'Lightning\\Pages\\Sitemap',
            'track' => 'Lightning\\Pages\\Track',
        ),
    ),
    'language' => 'en_us',
);
