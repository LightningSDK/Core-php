<?php

namespace lightningsdk\core\Database\Content;

use lightningsdk\core\Database\Content;

class PageContent extends Content {

    protected $table = 'page';

    public function getContent() {
        return [
            // Home page.
            [
                'url' => 'index',
                'title' => 'Welcome to Lightning',
                'site_map' => 1,
                'body' => '<p>This is the default page. To edit this page, log in as an admin. If you have not set up an admin yet, log into your server and run <pre>./lightning user create-admin</pre></p>',
                'right_column' => 1,
            ],
            // 404 Error page.
            [
                'url' => '404',
                'title' => '404 Not Found Lightning',
                'site_map' => 0,
                'body' => '<p>You are trying to access a page that does not exist.</p>',
                'right_column' => 1,
            ],
        ];
    }
}
