<?php

namespace Lightning\Model;

use Lightning\Tools\Database;

class SplitTest extends Object {
    const TABLE = 'split_test';
    const PRIMARY_KEY = 'split_test_id';

    public static function loadByLocator($locator) {
        $data = Database::getInstance()->selectRow(static::TABLE, ['locator' => $locator]);
        if (!empty($data)) {
            return new static($data);
        } else {
            return null;
        }
    }

    public static function loadOrCreateByLocator($locator) {
        $test = self::loadByLocator($locator);
        if (empty($test)) {
            $data = ['locator' => $locator];
            $data['split_test_id'] = Database::getInstance()->insert(static::TABLE, $data);
            return new static($data);
        } else {
            return $test;
        }
    }
}
