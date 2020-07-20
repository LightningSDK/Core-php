<?php

return [
    'template' => [
        'default' => ['template', 'lightningsdk/core'],
    ],
    'routes' => [
        'dynamic' => [
            '.*' => \lightningsdk\core\Pages\Page::class,
        ],
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
            'user' => \lightningsdk\core\Pages\User::class,
            'profile' => \lightningsdk\core\Pages\Profile::class,
            'message' => \lightningsdk\core\Pages\Message::class,

            // API
            'api/contact' => \lightningsdk\core\API\Contact::class,
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
            'job' => \lightningsdk\core\CLI\Job::class,
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
        'js' => [
            'lightningsdk/core' => [
                'js/prefix.js' => [
                    'dest' => 'lightning.min.js',
                    'requires_module' => ['lightningsdk/foundation']
                ],
                'js/components/*.js' => [
                    'dest' => 'lightning.min.js',
                    'requires_module' => ['lightningsdk/foundation']
                ],
                'js/suffix.js' => [
                    'dest' => 'lightning.min.js',
                    'requires_module' => ['lightningsdk/foundation']
                ],
            ],
            'ace' => [
                'node_modules/ace-builds/src/ace.js' => 'jsoneditor.min.js',
            ],
        ],
        'css' => [
            'lightningsdk/core' => [
                'sass/*.scss' => 'lightning.css',
            ],
            'jsoneditor' => [
                'node_modules/jsoneditor/src/scss/**/*.scss' => 'jsoneditor.min.css',
            ],
        ],
        'copy' => [
            'jsoneditor' => [
                'node_modules/jsoneditor/dist/img/**' => 'js/jsoneditor/img',
                'node_modules/jsoneditor/dist/jsoneditor.min.js' => 'js/jsoneditor',
            ],
            'videojs' => [
                'node_modules/video.js/dist/**' => 'js/videojs'
            ],
            'ckeditor/ckeditor' => [
                '**' => 'js/ckeditor',
            ],
            'lightningsdk/core' => [
                'js/ckeditor-plugins/**' => 'js/ckeditor/plugins',
            ],
            'studio-42/elfinder' => [
                '**' => 'js/elfinder',
            ],
        ],
        'sass' => [
            'includes' => [
                'lightning' => 'vendor/ligthningsdk/core/sass',
                'font-awesome' => 'vendor/components/font-awesome',
            ],
            'vars' => [
                '$jse-icons' => '/js/jsoneditor/img/jsoneditor-icons.svg',
            ],
        ],
        'npm' => [
            'jsoneditor@^9.0',
            'ace-builds@^1.4',
            'video.js@^7.8',
        ],
    ],
    'hmtlpurifier' => [
        'cache' => 'cache/htmlpurifier',
    ],
    'jobs' => [
        'session-cleanup' => [
            'class' => \lightningsdk\core\Jobs\SessionCleanup::class,
            'schedule' => '0 2 * * * *', // Every day at 2am
            'max_threads' => 1,
        ],
        'auto-mailer' => [
            'class' => \lightningsdk\core\Jobs\Mailer::class,
            'schedule' => '0 16 * * * *', // Every day at 4pm
            'max_threads' => 1,
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
