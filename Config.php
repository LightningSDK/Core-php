<?php

$conf = array(
    'overridable' => array(
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
        'remember_ttl' => 2592000,
        'password_ttl' => 432000,
        'cookie' => 'session',
    ),
    'user' => array(
        'login_url' => '/',
        'logout_url' => '/',
    ),
    'language' => 'en_us',
    'template_dir' => 'Source/Templates',
    'random_engine' => MCRYPT_DEV_URANDOM,
);
