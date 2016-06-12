<?php

$conf = array(
    'template' => array(
        'default' => 'template'
    ),
    'overridable' => array(
        'Lightning\\Model\\Blog' => 'Overridable\\Lightning\\Model\\Blog',
        'Lightning\\Model\\BlogPost' => 'Overridable\\Lightning\\Model\\BlogPost',
        'Lightning\\Model\\Calendar' => 'Overridable\\Lightning\\Model\\Calendar',
        'Lightning\\Model\\CMS' => 'Overridable\\Lightning\\Model\\CMS',
        'Lightning\\Model\\Message' => 'Overridable\\Lightning\\Model\\Message',
        'Lightning\\Model\\Page' => 'Overridable\\Lightning\\Model\\Page',
        'Lightning\\Model\\Permissions' => 'Overridable\\Lightning\\Model\\Permissions',
        'Lightning\\Model\\SocialAuth' => 'Overridable\\Lightning\\Model\\SocialAuth',
        'Lightning\\Model\\Token' => 'Overridable\\Lightning\\Model\\Token',
        'Lightning\\Model\\User' => 'Overridable\\Lightning\\Model\\User',
        'Lightning\\Tools\\ClientUser' => 'Overridable\\Lightning\\Tools\\ClientUser',
        'Lightning\\Tools\\Request' => 'Overridable\\Lightning\\Tools\\Request',
        'Lightning\\Tools\\Security\\Encryption' => 'Overridable\\Lightning\\Tools\\Security\\Encryption',
        'Lightning\\Tools\\Security\\Random' => 'Overridable\\Lightning\\Tools\\Security\\Random',
        'Lightning\\Tools\\Session' => 'Overridable\\Lightning\\Tools\\Session',
        'Lightning\\Tools\\SocialDrivers\\SocialMediaApi' => 'Overridable\\Lightning\\Tools\\SocialDrivers\\SocialMediaApi',
        'Lightning\\View\\API' => 'Overridable\\Lightning\\View\\API',
        'Lightning\\View\\Page' => 'Overridable\\Lightning\\View\\Page',
    ),
    'routes' => array(
        'cli_only' => array(
            'database' => 'Lightning\\CLI\\Database',
            'user' => 'Lightning\\CLI\\User',
            'security' => 'Lightning\\CLI\\Security',
        ),
    ),
    'session' => array(
        'remember_ttl' => 2592000, // 30 Days
        'password_ttl' => 432000, // 5 Days
        'app_ttl' => 7776000, // 90 Days
        'cookie' => 'session',
        'user_convert' => [
            'tracker_event' => 'tracker_event',
        ]
    ),
    'user' => array(
        'login_url' => '/',
        'logout_url' => '/',
    ),
    'ckfinder' => array(
        'content' => '/content/',
    ),
    'page' => array(
        'modification_date' => true,
    ),
    'redis' => [
        'socket' => 'localhost:6379',
        'default_ttl' => 600,

    ],
    'language' => 'en_us',
    'template_dir' => 'Source/Templates',
    'temp_dir' => HOME_PATH . '/../tmp',
    'random_engine' => MCRYPT_DEV_URANDOM,
);
