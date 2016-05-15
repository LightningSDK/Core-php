<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\API;
use Lightning\Model\CMS as CMSModel;

class CMS extends API {

    protected function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    public function postSave() {
        if (ClientUser::getInstance()->isAdmin()) {
            $name = Request::post('cms');
            $content = Request::post('content', 'html', '', '', true);
            CMSModel::insertOrUpdate(
                ['name' => $name, 'content' => $content, 'last_modified' => time()],
                ['content' => $content, 'last_modified' => time()]
            );
            Output::json(Output::SUCCESS);
        } else {
            Output::json(Output::ACCESS_DENIED);
        }
    }

    public function postSaveImage() {
        if (ClientUser::getInstance()->isAdmin()) {
            $name = Request::post('cms');
            $content = Request::post('content');
            $class = Request::post('class');
            CMSModel::insertOrUpdate(
                ['name' => $name, 'content' => $content, 'last_modified' => time(), 'class' => $class],
                ['content' => $content, 'last_modified' => time(), 'class' => $class]
            );
            Output::json(Output::SUCCESS);
        } else {
            Output::json(Output::ACCESS_DENIED);
        }
    }
}
