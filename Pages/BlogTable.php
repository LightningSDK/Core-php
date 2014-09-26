<?php

namespace Lightning\Pages;

use Lightning\Tools\Navigation;
use Lightning\Tools\Request;

class BlogTable extends Table {
    protected $trusted = true;

    protected $table = 'blog';

    protected $key = 'blog_id';

    protected $sort = 'time DESC';

    protected $links = array(
        'blog_category' => array(
            'index' => 'blog_blog_category',
            'key' => 'cat_id',
            'display_column' => 'category',
            'list' => 'compact'
        )
    );

    protected $preset = array(
        'user_id' => array('type' => 'hidden'),
        'time' => array('type' => 'datetime', 'unlisted' => true),
        'url' => array('type' => 'url', 'unlisted' => true),
        'body' => array('editor' => 'full'),
    );

    protected function initSettings() {
        if (Request::get('return') == 'view') {
            $this->post_actions['after_post'] = function($row) {
                Navigation::redirect('/' . $row['url'] . '.htm');
            };
        }
    }

//    protected $settings = array(
//        'action_file' => '/blog',
//    );
}
