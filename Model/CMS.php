<?php

namespace Overridable\Lightning\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;
use Source\Model\Site;

class CMS extends Object {

    const TABLE = 'cms';
    const PRIMARY_KEY = 'cms_id';

    public static function loadByName($name) {
        $content = Database::getInstance()->selectRow(static::TABLE, array('name' => $name));
        if ($content) {
            return new static($content);
        } else {
            return false;
        }
    }
}
