<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\Page;

class CMS extends Page {
    public function postSave() {
        if (ClientUser::getInstance()->isAdmin()) {
            $name = Request::post('cms');
            $content = Request::post('content', 'html', '', '', true);
            Database::getInstance()->insert('cms',
                array('name' => $name, 'content' => $content, 'last_modified' => time()),
                array('content' => $content, 'last_modified' => time())
            );
            Output::json(Output::SUCCESS);
        } else {
            Output::json(Output::ACCESS_DENIED);
        }
    }
}
