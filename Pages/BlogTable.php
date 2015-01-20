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
        'time' => array('type' => 'datetime', 'unlisted' => true),
        'url' => array('type' => 'url', 'unlisted' => true),
        'body' => array('editor' => 'full', 'upload' => true),
        'header_image' => array(
            'type' => 'image',
            'location' => 'img/blog',
            'weblocation' => '/img/blog',
        )
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
    }
}
