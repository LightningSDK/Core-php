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
            Database::getInstance()->insert('cms',
                array('name' => $name, 'content' => $content, 'last_modified' => time(), 'class' => $class),
                array('content' => $content, 'last_modified' => time(), 'class' => $class)
            );
            Output::json(Output::SUCCESS);
        } else {
            Output::json(Output::ACCESS_DENIED);
        }
    }
    
    public function postUpdateDate() {
        if (ClientUser::getInstance()->isAdmin()) {
            $id = Request::post('id');
            $key = Request::post('key');
            $column = Request::post('column');
            $table = Request::post('table');
            $m = Request::post("date_m");
            $d = Request::post("date_d");
            $y = Request::post("date_y");
            if ($m > 0 && $d > 0) {
                if ($y == 0) $y = date("Y");
                $value = gregoriantojd($m, $d, $y);
            } else {
                $value = 0;
            }
            Database::getInstance()->update($table,
                array($column => $value),
                array($key => $id)
            );
            Output::json(Output::SUCCESS);
        } else {
            Output::json(Output::ACCESS_DENIED);
        }
        
    }
}
