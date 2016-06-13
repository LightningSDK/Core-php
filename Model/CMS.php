<?php

namespace Overridable\Lightning\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;
use Source\Model\Site;

/**
 * Class CMS
 * @package Source\Model
 *
 * @parameter integer $id
 * @parameter integer $cms_id
 * @parameter string $note
 * @parameter string $name
 * @parameter string content
 * @parameter string class
 * @parameter integer last_modified
 */
class CMS extends Object {

    const TABLE = 'cms';
    const PRIMARY_KEY = 'cms_id';

    /**
     * @param $name
     * @return CMS
     */
    public static function loadByName($name) {
        $content = Database::getInstance()->selectRow(static::TABLE, array('name' => $name));
        if ($content) {
            return new static($content);
        } else {
            return false;
        }
    }
}
