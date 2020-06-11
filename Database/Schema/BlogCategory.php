<?php

namespace lightningsdk\core\Database\Schema;

use lightningsdk\core\Database\Schema;

class BlogCategory extends Schema {

    const TABLE = 'blog_category';

    public function getColumns() {
        return [
            'cat_id' => $this->autoincrement(),
            'category' => $this->varchar(32),
            'cat_url' => $this->varchar(32),
        ];
    }

    public function getKeys() {
        return [
            'primary' => 'cat_id',
            'cat_url' => [
                'columns' => ['cat_url'],
                'unique' => true,
            ],
        ];
    }
}
