<?php

namespace Lightning\Model;

class Object {
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
}
