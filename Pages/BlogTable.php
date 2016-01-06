<?php

namespace Lightning\Pages;

use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\ClientUser;
use Lightning\Model\Blog as BlogModel;

class BlogTable extends Table {
    protected $trusted = true;

    protected $table = BlogModel::BLOG_TABLE;

    protected $key = 'blog_id';

    protected $sort = 'time DESC';

    protected $links = array(
        BlogModel::CATEGORY_TABLE => array(
            'index' => BlogModel::BLOG_CATEGORY_TABLE,
            'key' => 'cat_id',
            'display_column' => 'category',
            'list' => 'compact'
        )
    );

    protected $preset = array(
        'user_id' => array('type' => 'hidden'),
        'time' => array('type' => 'datetime'),
        'url' => array('type' => 'url', 'unlisted' => true),
        'body' => array('editor' => 'full', 'upload' => true),
    );

    protected function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    protected function initSettings() {
        if (Request::get('return') == 'view') {
            $this->post_actions['after_post'] = function($row) {
                Navigation::redirect('/' . $row['url'] . '.htm');
            };
        }
        $this->preset['user_id']['default'] = ClientUser::getInstance()->id;
        $this->preset['url']['submit_function'] = function(&$output) {
            $output['url'] = Request::post('url', 'url') ?: Request::post('title', 'url');
        };
        $this->preset['header_image'] = array(
            'type' => 'image',
            'location' => BlogModel::IMAGE_PATH,
            'weblocation' => '/' . BlogModel::IMAGE_PATH,
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
