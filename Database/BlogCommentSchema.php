<?php

namespace Lightning\Schema;

use Lightning\Tools\DatabaseSchema;

class BlogCommentSchema extends DatabaseSchema {

    protected $table = 'blog_comment';

    public function getColumns() {
        return array(
            'blog_comment_id' => $this->autoincrement(),
            'blog_id' => $this->int(true),
            'user_id' => $this->int(true),
            'ip_address' => $this->int(true),
            'email_address' => $this->varchar(128),
            'website' => $this->varchar(128),
            'name' => $this->varchar(128),
            'comment' => $this->varchar(255),
            'approved' => $this->int(true, DatabaseSchema::TINYINT),
            'time' => $this->int(),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'blog_comment_id',
        );
    }
}
