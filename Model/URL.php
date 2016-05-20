<?php

namespace Lightning\Model;

use Lightning\Tools\Database;
use Overridable\Lightning\Tools\Request;

class URL extends Object {
    const TABLE = 'url';
    const PRIMARY_KEY = 'url_id';
    const MAX_LENGTH = 255;

    public static function getCurrentUrlId() {
        $url = substr(Request::getURL(), 0, static::MAX_LENGTH);
        $db = Database::getInstance();
        if (!$id = $db->selectFieldQuery([
            'select' => static::PRIMARY_KEY,
            'from' => static::TABLE,
            'where' => ['url' => ['LIKE', $url]],
        ], static::PRIMARY_KEY)) {
            $id = $db->insert(static::TABLE, ['url' => $url], true);
            if (empty($id)) {
                $id = $db->selectField('url_id', static::TABLE, ['url' => $url]);
            }
        }
        return $id;
    }
}
