<?php

return [
    'template' => [
        'default' => ['template', 'lightningsdk/core'],
    ],
    'routes' => [
        'static' => [
            '' => \lightningsdk\core\Pages\Page::class,
            'admin/mailing/send' => \lightningsdk\core\Pages\Mailing\Send::class,
            'admin/tracker' => \lightningsdk\core\Pages\AdminTracker::class,
            'admin/cms' => \lightningsdk\core\Pages\Admin\CMS::class,
            'admin/roles' => \lightningsdk\core\Pages\Admin\Roles::class,
            'admin/permissions' => \lightningsdk\core\Pages\Admin\Permissions::class,
            'admin/splittests' => \lightningsdk\core\Pages\Admin\SplitTests::class,
            'admin/social/auth' => \lightningsdk\core\Pages\SocialSharing\Auth::class,
            'admin/social/share' => \lightningsdk\core\Pages\SocialSharing\Share::class,
            'robots.txt' => \lightningsdk\core\Pages\Robots::class,
            'sitemap' => \lightningsdk\core\Pages\Sitemap::class,
            'track' => \lightningsdk\core\Pages\Track::class,
            'landing' => \lightningsdk\core\Pages\OptIn::class,
            'user' => \lightningsdk\core\Pages\User::class,
            'profile' => \lightningsdk\core\Pages\Profile::class,

            // API
            'api/contact' => \lightningsdk\core\API\Contact::class,
            'api/optin' => \lightningsdk\core\API\Optin::class,
            'api/user' => \lightningsdk\core\API\User::class,

            // Image admin
            'elfinder' => \lightningsdk\core\Pages\ElFinder::class,
            'api/elfinder' => \lightningsdk\core\API\ElFinder::class,
            'imageBrowser' => \lightningsdk\core\Pages\ImageBrowser::class,

            'admin/contact' => \lightningsdk\core\Pages\Admin\Contact::class,
            'admin/mailing/lists' => \lightningsdk\core\Pages\Admin\Mailing\Lists::class,
            'admin/mailing/messages' => \lightningsdk\core\Pages\Admin\Mailing\Messages::class,
            'admin/mailing/stats' => \lightningsdk\core\Pages\Admin\Mailing\Stats::class,
            'admin/mailing/templates' => \lightningsdk\core\Pages\Admin\Mailing\Templates::class,
            'admin/pages' => \lightningsdk\core\Pages\Admin\Pages::class,
            'admin/widgets' => \lightningsdk\core\Pages\Admin\Pages::class,
            'admin/users' => \lightningsdk\core\Pages\Admin\Users::class,
        ],
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
        'css' => [
            'include' => [
                'core' => '/css/lightning.css',
            ],
        ],
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
            'script' => \lightningsdk\core\View\Script::class,
            'iframe' => \lightningsdk\core\View\Iframe::class,
            'social-share' => \lightningsdk\core\View\SocialMedia\Share::class,
            'social-links' => \lightningsdk\core\View\SocialMedia\Links::class,
            'social-follow' => \lightningsdk\core\View\SocialMedia\Follow::class,
            'cms' => \lightningsdk\core\View\CMS::class,
            'widget' => \lightningsdk\core\View\Widget::class,
        ],
    ],
    'sitemap' => [
        'pages' => \lightningsdk\core\Model\Page::class,
    ],
    'language' => 'en_us',
    'template_dir' => 'Source/Templates',
    'temp_dir' => HOME_PATH . '/../tmp',
    'compiler' => [
        'sass' => [
            'includes' => [
                'lightning' => 'vendor/ligthningsdk/core/sass'
            ],
        ],
    ],
    'menus' => [
        'admin' => [
            'Content' => [
                'children' => [
                    'Pages' => '/admin/pages',
                    'Widgets' => '/admin/widgets',
                ],
            ],
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
