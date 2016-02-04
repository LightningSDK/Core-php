<?php

namespace Overridable\Lightning\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;
use Source\Model\Site;

class CMS extends Object {
    public static function loadByName($name) {
        $content = Database::getInstance()->selectRow('cms', array('name' => $name));
        if ($content) {
            return new static($content);
        } else {
            return false;
        }
    }
}
