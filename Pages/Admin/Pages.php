<?php

namespace Lightning\Pages\Admin;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

class Pages extends Table {

    public function __construct() {
        ClientUser::requireAdmin();
        parent::__construct();
    }

    protected $searchable = true;
    protected $search_fields = array('title', 'url', 'body');

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
        'body' => array(
            'upload' => true
        ),
        'site_map' => array(
            'type' => 'checkbox',
        ),
        'last_update' => array(
            'type' => 'datetime',
        )
    );
}
