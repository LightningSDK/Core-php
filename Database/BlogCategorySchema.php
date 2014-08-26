<?php

namespace Lightning\Schema;

use Lightning\Tools\DatabaseSchema;

class BlogCategorySchema extends DatabaseSchema {

    protected $table = 'blog_category';

    public function getColumns() {
        return array(
            'cat_id' => $this->autoincrement(),
            'category' => $this->varchar(32),
            'car_url' => $this->varchar(32),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'cat_id',
            'car_url' => array(
                'columns' => array('car_url'),
                'unique' => true,
            ),
        );
    }
}
