<?php

$conf = [
    'template' => [
        'default' => ['template', 'Lightning'],
    ],
    'routes' => [
        'cli_only' => [
            'database' => \Lightning\CLI\Database::class,
            'user' => \Lightning\CLI\User::class,
            'security' => \Lightning\CLI\Security::class,
            'gulp' => \Lightning\CLI\Gulp::class,
        ],
    ],
    'session' => [
        'remember_ttl' => 2592000, // 30 Days
        'password_ttl' => 432000, // 5 Days
        'app_ttl' => 7776000, // 90 Days
        'cookie' => 'session',
        'user_convert' => [
            'tracker_event' => 'tracker_event',
        ]
    ],
    'user' => [
        'login_url' => '/',
        'logout_url' => '/',
    ],
    'ckfinder' => [
        'content' => '/content/',
    ],
    'page' => [
        'modification_date' => true,
    ],
    'redis' => [
        'socket' => 'localhost:6379',
        'default_ttl' => 600,
    ],
    'markup' => [
        'renderers' => [
            'form' => \Lightning\View\Form::class,
            'template' => \Lightning\Tools\Template::class,
            'youtube' => \Lightning\View\Video\YouTube::class,
            'input' => \Lightning\View\Field::class,
            'blog' => \Lightning\Pages\Blog::class,
            'script' => \Lightning\View\Script::class,
            'iframe' => \Lightning\View\Iframe::class,
            'social-links' => \Lightning\View\SocialMedia\Links::class,
            'social-follow' => \Lightning\View\SocialMedia\Follow::class,
            'cms' => \Lightning\View\CMS::class,
        ],
    ],
    'sitemap' => [
        'pages' => \Lightning\Model\Page::class,
        'blog' => \Lightning\Model\Blog::class,
    ],
    'language' => 'en_us',
    'template_dir' => 'Source/Templates',
    'temp_dir' => HOME_PATH . '/../tmp',
    'menus' => [
        'admin' => [
            'Blog' => [
                'children' => [
                    'Posts' => 'admin/blog/edit',
                    'Categories' => 'admin/blog/categories'
                ],
            ],
            'Pages' => '/admin/pages',
            'Users' => '/admin/users',
            'Mailing' => [
                'children' => [
                    'Lists' => '/admin/mailing/lists',
                    'Templates' => '/admin/mailing/templates',
                    'Messages' => '/admin/mailing/messages',
                ],
            ],
        ],
    ],
];
