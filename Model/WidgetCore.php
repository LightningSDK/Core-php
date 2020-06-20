<?php

namespace lightningsdk\core\Model;

use lightningsdk\core\Tools\Database;

class WidgetCore extends BaseObject {

    const TABLE = 'widget';
    const PRIMARY_KEY = 'widget_id';

    /**
     * Load a widget by the name.
     *
     * @param string $name
     *   The name of the widget.
     *
     * @return static
     *   The new object
     *
     * @throws \Exception
     */
    public static function loadByName($name) {
        if ($data = Database::getInstance()->selectRow(static::TABLE, ['name' => $name])) {
            return new static($data);
        } else {
            return null;
        }
    }
}
