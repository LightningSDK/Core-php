<?php

namespace Lightning\Pages\Admin;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Model\Permissions;

class Pages extends Table {

    const TABLE = 'page';
    const PRIMARY_KEY = 'page_id';

    /**
     * @return boolean
     *
     * @throws \Exception
     */
    public function hasAccess() {
        return ClientUser::requirePermission(Permissions::EDIT_PAGES);
    }

    protected $searchable = true;
    protected $search_fields = ['title', 'url', 'body'];

    protected $nav = 'admin_pages';
    protected $sortable = true;
    protected $trusted = true;
    protected $duplicatable = true;
    protected $preset = [
        'page_id' => [
            'type' => 'hidden',
        ],
        'url' => [
            'type' => 'url',
            'display_name' => 'URL',
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
        'css' => [
            'type' => 'plaintext',
        ],
        'site_map' => [
            'type' => 'checkbox',
            'default' => true,
        ],
        'last_update' => [
            'type' => 'datetime',
            'editable' => false,
        ],
        'right_column' => [
            'type' => 'checkbox',
            'default' => true,
        ],
        'hide_header' => 'checkbox',
        'hide_menu' => 'checkbox',
        'hide_social' => 'checkbox',
        'hide_footer' => 'checkbox',
        'full_width' => 'checkbox',
        'use_parser' => 'checkbox',
        'modules' => 'json',
    ];

    /**
     * @throws \Exception
     */
    protected function initSettings() {
        $this->preset['url']['submit_function'] = function(&$output) {
            // The url will be scrubbed from the supplied value
            $output['url'] = Request::post('url')
                // Or will fallback to a slugged version of the title.
                ?: Request::post('title');
        };

        $this->preset['last_update']['submit_function'] = function(&$output) {
            $output['last_update'] = time();
        };

        $this->preset['language'] = \Lightning\Tools\Configuration::get('language.available');

        if (\Lightning\Tools\Configuration::get('css.editable')) {
            $this->custom_buttons['css'] = [
                'url' => '/admin/css',
                'type' => Table::CB_LINK,
                'target' => '_blank',
                'text' => 'Edit CSS',
            ];
        }

        if (!empty($this->id)) {
            $this->getRow();
            $this->custom_buttons['view'] = [
                'url' => '/' . $this->list['url'],
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
                    return '<a href="/' . $row['url'] . '"><img src="/images/lightning/resume.png" /></a>';
                }
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    public function getView() {
        $this->getRow();
        Navigation::redirect('/' . $this->list['url']);
    }
}
