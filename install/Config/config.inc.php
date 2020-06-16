<?php

$conf = [
    'database' => 'mysql:user=user;password=pass;host=localhost;dbname=db',
    'user' => [
        // Generate a new key by going to the Lightning directory and running
        // ./lightning security generate-aes-key
        // **** THIS KEY IS INCLUDED WITH THE DISTRIBUTION AND IS NOT SECURE ****
        'key' => 'o2sdxfGwHn2YGcQ3Xh2Z8p5y/BP0dHdbtdmU3ATdMwE=',
        'multiple_devices' => false,
        'min_password_length' => 6,
        'requires_confirmation' => false,
    ],
    'timezone' => 'America/Chicago',
    'session' => [
        'single_ip' => true,
    ],
    'template' => [
        'default' => ['template', 'lightningsdk/core'],
    ],
    'social' => [
        'store_images' => true,
        'facebook' => [
            'appid' => '',
            'secret' => '',
        ],
        'twitter' => [
            'key' => '',
            'secret' => '',
        ],
        'google' => [
            'client_id' => '',
            'secret' => '',
        ],
        // Google uses a separate client/secret for login through iphone/android.
        'google-app' => [
            'client_id' => '',
            'secret' => '',
        ],
        'share' => [
            'facebook' => true,
            'google' => true,
            'twitter' => true,
            'pinterest' => false,
            'linkedin' => true,
            'email' => true,
        ],
    ],
    'site' => [
        'mail_from' => 'donotreply@Website.com',
        'mail_from_name' => 'My Mailer',
        'name' => 'My Website',
        'domain' => 'Website.com',
        'email_domain' => 'www.Website.com',
        'log' => '../logs/web.log',
        'logtype' => 'stacktrace',
    ],
    'contact' => [
        'subject' => 'Message from Website.com',
        'auto_responder' => 0, // Change to this a message_id of an auto respond.
        'spam_test' => false, // Whether to tell them to immediately look for the auto respond in their junk folder.
        'to' => ['youremail@gmail.com'],
        'cc' => [],
        'bcc' => [],
        'optin' => false,
    ],
    'messages' => [
        'spamFilters' => [
            \lightningsdk\core\Tools\Messages\BlackList::class,
        ],
        'maxAllowableScore' => 5,
    ],
    'mailer' => [
        'test' => [],
        'spam_test' => [],
        'spam_test_from' => null,
        'mail_from' => null,
        'mail_from_name' => null,
        'bounce_address' => null,
        'default_template' => null,
        'default_list' => null,
        'mail_template' => null,
        'confirm_message' => null, // Set a message_id here to enable double confirmation.
        'dkim_key' => '',
        'dkim_domain' => '',
        'dkim_selector' => '', // This is the subdomain where your dkim key is found. For example if your dkim record is dkim._domainkey.mydomain.com, then this value would be set to 'dkim'.
    ],
    'tracker' => [
        'allow_unencrypted' => true,
        // Generate a new key by going to the Lightning directory and running
        // ./lightning security generate-aes-key
        // **** THIS KEY IS INCLUDED WITH THE DISTRIBUTION AND IS NOT SECURE ****
        'key' => '0Xx+v7xGDanBpTgDoIqwlA==:JPJdzm5ifvePYztVj1ICrQ==',
    ],
    'html_editor' => [
        'editor' => 'text',
        'browser' => '',
    ],
    'imageBrowser' => [
        'containers' => [
            'images' => [
                // Relative to the home directory
                'storage' => 'images',
                // Will be prefixed to an image file. Include the trailing slash.
                'url' => '/images/',
            ],
        ],
        'type' => 'elfinder',
    ],
    'meta_data' => [
        'title' => '',
        'keywords' => '',
        'description' => '.',
    ],
    'google_analytics_id' => '',
    'use_mobile_site' => true,
    'recaptcha' => [
        'public' => '',
        'private' => '',
        'invisible' => [
            'public' => '',
            'private' => '',
        ],
    ],
    'web_root' => 'http://www.Website.com',
    'daemon' => [
        'max_threads' => 5,
        'log' => '../logs/daemon.log',
    ],
    'cli' => [
        'log' => '../logs/cli.log',
    ],
    'jobs' => [
        'session-cleanup' => [
            'class' => 'lightningsdk\\core\\Jobs\\UserCleanup',
            'schedule' => '0 2 * * * *', // Every day at 2am
            'max_threads' => 1,
        ],
    ],
    'routes' => [
        'dynamic' => [
            '.*' => 'lightningsdk\\core\\Pages\\Page',
        ],
        'static' => [
            '' => 'lightningsdk\\core\\Pages\\Page',
            'user' => 'lightningsdk\\core\\Pages\\User',
            'contact' => 'lightningsdk\\core\\Pages\\Contact',
            'page' => 'lightningsdk\\core\\Pages\\Page',
            'message' => 'lightningsdk\\core\\Pages\\Message',
            'track' => 'lightningsdk\\core\\Pages\\Track',
            'landing' => 'lightningsdk\\core\\Pages\\OptIn',
            'profile' => 'lightningsdk\\core\\Pages\\Profile',

            // SEO
            'sitemap' => 'lightningsdk\\core\\Pages\\Sitemap',

            // Admin
            'admin/social/auth' => 'lightningsdk\\core\\Pages\\SocialSharing\\Auth',
            'admin/social/share' => 'lightningsdk\\core\\Pages\\SocialSharing\\Share',
            'admin/mailing/lists' => 'lightningsdk\\core\\Pages\\Mailing\\Lists',
            'admin/mailing/messages' => 'lightningsdk\\core\\Pages\\Mailing\\Messages',
            'admin/mailing/send' => 'lightningsdk\\core\\Pages\\Mailing\\Send',
            'admin/mailing/stats' => 'lightningsdk\\core\\Pages\\Mailing\\Stats',
            'admin/mailing/templates' => 'lightningsdk\\core\\Pages\\Mailing\\Templates',
            'admin/tracker' => 'lightningsdk\\core\\Pages\\AdminTracker',
            'admin/pages' => 'lightningsdk\\core\\Pages\\Admin\\Pages',
            'admin/users' => 'lightningsdk\\core\\Pages\\Admin\\Users',
            'admin/cms' => 'lightningsdk\\core\\Pages\\Admin\\CMS',
            'admin/roles' => 'lightningsdk\\core\\Pages\\Admin\\Roles',
            'admin/permissions' => 'lightningsdk\\core\\Pages\\Admin\\Permissions',
            'admin/contact' => 'lightningsdk\\core\\Pages\\Admin\\Contact',
        ],
        'cli_only' => [
            'bounce' => 'lightningsdk\\core\\CLI\\BouncedEmail',
            'test' => 'Source\\CLI\\Test',
        ],
    ],
    'language' => [
        'default' => 'en_us',
        'available' => [
            'en_us' => 'English',
        ],
        'url_method' => 'subdomain',
    ],
];
