<?php

namespace Lightning\Database\Content;

use Lightning\Database\Content;

class PageContent extends Content {

    protected $table = 'page';

    public function getContent() {
        return array(
            // Home page.
            array(
                'url' => 'index',
                'title' => 'Welcome to Lightning',
                'site_map' => 1,
                'body' => '<h1>Welcome to lightning.</h1>
                <p>This is the default page. To edit this page, log in as an admin. If you have not set up an admin yet, log into your server and run <pre>./lightning user create-admin</pre></p>',
            ),
            // 404 Error page.
            array(
                'url' => '404',
                'title' => 'Welcome to Lightning',
                'site_map' => 0,
                'body' => '<h1>Not Found</h1>
                <p>You are trying to access a page that does not exist.</p>',
            ),
        );
    }
}
