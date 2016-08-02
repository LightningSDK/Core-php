<?php

namespace Lightning\Pages;

use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Template;
use Lightning\Tools\ClientUser;

class BlogCategories extends Table {
    protected $table = 'blog_category';

    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    public function initSettings() {
        Template::getInstance()->set('full_width', true);
        $this->preset['cat_url'] = [
            'submit_function' => function(&$output){
                $output['cat_url'] = Request::post('cat_url', 'cat_url') ?: Request::post('category', 'url');
            }
        ];
    }
}
