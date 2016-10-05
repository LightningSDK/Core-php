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
    protected $search_fields = ['title', 'url', 'body'];

    protected $nav = 'admin_pages';
    protected $table = 'page';
    protected $sortable = true;
    protected $trusted = true;
    protected $preset = [
        'page_id' => [
            'type' => 'hidden',
        ],
        'keywords' => [
            'unlisted' => true,
            'type' => 'textarea',
        ],
        'description' => [
            'unlisted' => true,
            'type' => 'textarea',
        ],
        'body' => [
            'upload' => true,
            'type' => 'html',
            'div' => true,
        ],
        'site_map' => [
            'type' => 'checkbox',
            'default' => true,
        ],
        'last_update' => [
            'type' => 'datetime',
        ],
        'right_column' => [
            'type' => 'checkbox',
            'default' => true,
        ],
        'full_width' => 'checkbox',
    ];

    protected function initSettings() {
        $this->preset['url']['submit_function'] = function(&$output) {
            $output['url'] = Request::post('url', 'url') ?: Request::post('title', 'url');
        };

        if (!empty($this->id)) {
            $this->getRow();
            $this->custom_buttons['view'] = [
                'url' => '/' . $this->list['url'] . '.html',
                'type' => Table::CB_LINK,
                'target' => '_blank',
                'text' => 'View',
            ];
        } else if (Request::get('action') == 'new') {
            $this->preset['url']['default'] = Request::get('url');
        }

        $this->action_fields = [
            'view' => [
                'display_name' => 'View',
                'type' => 'html',
                'html' => function($row) {
                    return '<a href="/' . $row['url'] . '.html"><img src="/images/lightning/resume.png" /></a>';
                }
            ],
        ];
    }
}
