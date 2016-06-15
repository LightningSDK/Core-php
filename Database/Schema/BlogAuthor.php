<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class BlogAuthor extends Schema {

    const TABLE = 'blog_author';

    public function getColumns() {
        return array(
            'user_id' => $this->int(true),
            'author_description' => $this->text(),
            'author_name' => $this->varchar(64),
            'author_image' => $this->varchar(128),
            'author_url' => $this->varchar(128),
        );
    }

    public function getKeys() {
        return array(
            'primary' => array(
                'columns' => array('user_id'),
                'auto_increment' => false,
            ),
        );
    }
}
