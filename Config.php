<?php

$conf = [
    'template' => [
        'default' => ['template', 'lightningsdk/core'],
    ],
    'routes' => [
        'cli_only' => [
            'database' => \lightningsdk\core\CLI\Database::class,
            'user' => \lightningsdk\core\CLI\User::class,
            'security' => \lightningsdk\core\CLI\Security::class,
            'gulp' => \lightningsdk\core\CLI\Gulp::class,
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
            'form' => \lightningsdk\core\View\Form::class,
            'template' => \lightningsdk\core\Tools\Template::class,
            'youtube' => \lightningsdk\core\View\Video\YouTube::class,
            'input' => \lightningsdk\core\View\Field::class,
            'blog' => \lightningsdk\core\Pages\Blog::class,
            'script' => \lightningsdk\core\View\Script::class,
            'iframe' => \lightningsdk\core\View\Iframe::class,
            'social-links' => \lightningsdk\core\View\SocialMedia\Links::class,
            'social-follow' => \lightningsdk\core\View\SocialMedia\Follow::class,
            'cms' => \lightningsdk\core\View\CMS::class,
        ],
    ],
    'sitemap' => [
        'pages' => \lightningsdk\core\Model\Page::class,
        'blog' => \lightningsdk\core\Model\Blog::class,
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
