<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class BlogBlogCategory extends Schema {

    protected $table = 'blog_blog_category';

    public function getColumns() {
        return array(
            'blog_id' => $this->int(true),
            'cat_id' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'blog_id' => array(
                'columns' => array('blog_id'),
            ),
            'cat_id' => array(
                'columns' => array('cat_id'),
            ),
        );
    }
}
