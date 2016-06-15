<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class BlogCategory extends Schema {

    const TABLE = 'blog_category';

    public function getColumns() {
        return array(
            'cat_id' => $this->autoincrement(),
            'category' => $this->varchar(32),
            'cat_url' => $this->varchar(32),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'cat_id',
            'cat_url' => array(
                'columns' => array('cat_url'),
                'unique' => true,
            ),
        );
    }
}
