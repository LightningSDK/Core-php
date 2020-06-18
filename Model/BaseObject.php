<?php

namespace Lightning\Model;

use JsonSerializable;

class BaseObject implements JsonSerializable {
    use ObjectDataStorage;
    use ObjectDatabaseStorage;

    /**
     * The primary key form the database.
     */
    const PRIMARY_KEY = '';

    /**
     * The table where the object is stored.
     */
    const TABLE = '';

    public function jsonSerialize() {
        return $this->__data ?? null;
    }
}
