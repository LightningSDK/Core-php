<?php

namespace Lightning\Pages\Admin;

use Lightning\Pages\Table;

class Pages extends Table {
    protected $nav = 'admin_pages';
    protected $table = 'page';
    protected $sortable = true;
    protected $preset = array(
        'page_id' => array(
            'type' => 'hidden',
        ),
        'keywords' => array(
            'unlisted' => true,
            'type' => 'textarea',
        ),
        'description' => array(
            'unlisted' => true,
            'type' => 'textarea',
        ),
        'site_map' => array(
            'type' => 'checkbox',
        ),
        'last_update' => array(
            'type' => 'datetime',
        )
    );
}
