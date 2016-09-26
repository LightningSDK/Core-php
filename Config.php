<?php

$conf = array(
    'template' => array(
        'default' => 'template'
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
