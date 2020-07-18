<?php

namespace lightningsdk\core\Model\Mailing;

use lightningsdk\core\Model\BaseObject;
use lightningsdk\core\Tools\Database;

class ListsCore extends BaseObject {
    const TABLE = 'message_list';
    const PRIMARY_KEY = 'message_list_id';

    public static function getOptions() {
        return Database::getInstance()->selectColumn('message_list', 'name', [], 'message_list_id');
    }

    public static function getAllIDs() {
        return Database::getInstance()->selectColumn('message_list', 'message_list_id');
    }
}
