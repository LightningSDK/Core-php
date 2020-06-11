<?php

namespace lightningsdk\core\Tools;

use lightningsdk\core\Model\ObjectDatabaseStorage;

class SingletonObject extends Singleton {
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
