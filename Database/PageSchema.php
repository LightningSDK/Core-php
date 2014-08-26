<?php

namespace Lightning\Schema;

use Lightning\Tools\DatabaseSchema;

class PageSchema extends DatabaseSchema {

    protected $table = 'page';

    public function getColumns() {
        return array(
            'page_id' => $this->autoincrement(),
            'title' => $this->varchar(255),
            'url' => $this->varchar(128),
            'keywords' => $this->varchar(255),
            'description' => $this->varchar(255),
            'body' => $this->text(DatabaseSchema::MEDIUMTEXT),
            'site_map' => $this->int(true, DatabaseSchema::TINYINT),
            'frequency' => $this->int(true, DatabaseSchema::TINYINT),
            'priority' => $this->int(true),
            'last_update' => $this->int(true),
        );
    }

    public function getKeys() {
        return array(
            'primary' => 'page_id',
            'url' => array(
                'columns' => array('url'),
                'unique' => true,
            ),
        );
    }
}
