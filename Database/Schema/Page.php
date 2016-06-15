<?php

namespace Lightning\Database\Schema;

use Lightning\Database\Schema;

class Page extends Schema {

    const TABLE = 'page';

    public function getColumns() {
        return array(
            'page_id' => $this->autoincrement(),
            'title' => $this->varchar(255),
            'url' => $this->varchar(128),
            'menu_context' => $this->varchar(64),
            'keywords' => $this->varchar(255),
            'description' => $this->varchar(255),
            'body' => $this->text(Schema::MEDIUMTEXT),
            'site_map' => $this->int(true, Schema::TINYINT),
            'frequency' => $this->int(true, Schema::TINYINT),
            'layout' => $this->int(true, Schema::TINYINT),
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
