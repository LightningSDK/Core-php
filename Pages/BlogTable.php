<?php

namespace Lightning\Pages;

class BlogTable extends Table {
    protected $trusted = true;

    protected $table = 'blog';

    protected $key = 'blog_id';

    protected $sort = 'time DESC';

    protected $links = array(
        'categories' => array(
            'index' => 'blog_blog_category',
            'key' => 'cat_id',
            'display_column' => 'category',
            'list' => true
        )
    );

    protected $preset = array(
        'time' => array('type' => 'datetime'),
        'url' => array('type' => 'url'),
        'body' => array('editor' => 'full'),
    );

    protected function initSettings() {
    }

//    protected $settings = array(
//        'action_file' => '/blog',
//    );
}
