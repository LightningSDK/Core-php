<?php

namespace Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Overridable\Lightning\Tools\Request;

class URL extends Object {
    const TABLE = 'url';
    const PRIMARY_KEY = 'url_id';
    const MAX_LENGTH = 255;

    /**
     * Get a URL ID for the current web URL.
     *
     * @return integer
     *   The URL ID.
     */
    public static function getCurrentUrlId() {
        return self::getURLId(substr(Request::getURL(), 0, static::MAX_LENGTH));
    }

    /**
     * Get a URL ID for a specified URL.
     *
     * @param string $url
     *
     * @return integer
     *   The URL ID.
     */
    public static function getURLId($url) {
        $db = Database::getInstance();
        if (!$id = $db->selectFieldQuery([
            'select' => static::PRIMARY_KEY,
            'from' => static::TABLE,
            'where' => ['url' => ['LIKE', $url]],
        ], static::PRIMARY_KEY)) {
            $id = $db->insert(static::TABLE, ['url' => $url]);
        }
        return $id;
    }

    public static function getAbsolute($url) {
        if (preg_match('/https?:\/\//i', $url)) {
            return $url;
        } elseif (substr($url, 0, 1) == '/') {
            return Configuration::get('web_root') . $url;
        } else {
            return Configuration::get('web_root') . '/' . $url;
        }
    }
}
