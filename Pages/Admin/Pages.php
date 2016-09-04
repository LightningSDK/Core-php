<?php

namespace Lightning\Pages\Admin;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Request;

class Pages extends Table {

    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    protected $searchable = true;
    protected $search_fields = array('title', 'url', 'body');

    protected $nav = 'admin_pages';
    protected $table = 'page';
    protected $sortable = true;
    protected $trusted = true;
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
            'upload' => true,
            'type' => 'html',
            'div' => true,
        ),
        'site_map' => array(
            'type' => 'checkbox',
            'default' => true,
        ),
        'last_update' => array(
            'type' => 'datetime',
        )
    );

    protected function initSettings() {
        $this->preset['url']['submit_function'] = function(&$output) {
            $output['url'] = Request::post('url', 'url') ?: Request::post('title', 'url');
        };

        $this->action_fields = array(
            'view' => array(
                'display_name' => 'View',
                'type' => 'html',
                'html' => function($row) {
                    return '<a href="/' . $row['url'] . '.html"><img src="/images/lightning/resume.png" /></a>';
                }
            ),
        );
    }
}
