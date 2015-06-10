<?php

$conf = array(
    'template' => array(
        'default' => 'template'
    ),
    'overridable' => array(
        'Lightning\\View\\Page' => 'Overridable\\Lightning\\View\\Page',
        'Lightning\\View\\API' => 'Overridable\\Lightning\\View\\API',
        'Lightning\\Model\\Blog' => 'Overridable\\Lightning\\Model\\Blog',
        'Lightning\\Model\\User' => 'Overridable\\Lightning\\Model\\User',
        'Lightning\\Tools\\Session' => 'Overridable\\Lightning\\Tools\\Session',
        'Lightning\\Tools\\Request' => 'Overridable\\Lightning\\Tools\\Request',
        'Lightning\\Tools\\ClientUser' => 'Overridable\\Lightning\\Tools\\ClientUser',
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
    'language' => 'en_us',
    'template_dir' => 'Source/Templates',
    'random_engine' => MCRYPT_DEV_URANDOM,
);
