<?php

namespace Lightning\Schema;

use Lightning\Tools\DatabaseSchema;

class BlogSchema extends DatabaseSchema {

    protected $table = 'blog';

    public function getColumns() {
        return array(
            'blog_id' => $this->autoincrement(),
            'user_id' => $this->int(true),
            'time' => $this->int(true),
            'title' => $this->varchar(255),
            'url' => $this->varchar(128),
            'body' => $this->text(DatabaseSchema::MEDIUMTEXT),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'blog_id',
            'url' => array(
                'columns' => array('url'),
                'unique' => true,
            ),
        );
    }
}
