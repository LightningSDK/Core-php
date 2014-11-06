<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class CMS extends Schema {

    protected $table = 'blog';

    public function getColumns() {
        return array(
            'cms_id' => $this->autoincrement(),
            'note' => $this->varchar(255),
            'name' => $this->varchar(128),
            'content' => $this->text(Schema::MEDIUMTEXT),
            'last_modified' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'cms_id',
            'name' => array(
                'columns' => array('name'),
                'unique' => true,
            ),
        );
    }
}
