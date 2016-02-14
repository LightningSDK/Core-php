<?php

namespace Lightning\Pages;

use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\ClientUser;
use Lightning\Model\Blog as BlogModel;
use Lightning\Tools\Template;

class BlogTable extends Table {
    protected $trusted = true;

    protected $table = BlogModel::BLOG_TABLE;

    protected $key = 'blog_id';

    protected $sort = 'time DESC';

    protected $links = [
        BlogModel::CATEGORY_TABLE => [
            'index' => BlogModel::BLOG_CATEGORY_TABLE,
            'key' => 'cat_id',
            'display_column' => 'category',
            'list' => 'compact'
        ]
    ];

    protected $preset = array(
        'user_id' => [
            'type' => 'hidden'
        ],
        'time' => [
            'type' => 'datetime'
        ],
        'url' => [
            'type' => 'url',
            'unlisted' => true
        ],
        'body' => [
            'upload' => true,
            'type' => 'html',
            'div' => true,
        ],
    );

    protected function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    protected function afterPost() {
        if (Request::get('return') == 'view') {
            Navigation::redirect('/' . $this->list['url'] . '.htm');
        }
    }

    protected function initSettings() {
        Template::getInstance()->set('full_width', true);

        $this->preset['user_id']['default'] = ClientUser::getInstance()->id;
        $this->preset['url']['submit_function'] = function(&$output) {
            $output['url'] = Request::post('url', 'url') ?: Request::post('title', 'url');
        };
        $this->preset['header_image'] = array(
            'type' => 'image',
            'browser' => true,
            'container' => 'images',
            'format' => 'jpg',
        );

        $this->action_fields = array(
            'view' => array(
                'display_name' => 'View',
                'type' => 'html',
                'html' => function($row) {
                    return '<a href="/' . $row['url'] . '.htm"><img src="/images/lightning/resume.png" /></a>';
                }
            ),
        );
    }
}
