<?php

namespace Lightning\Tools;

use MongoClient;

class Mongo extends Singleton {

    /**
     * Get the default database instance.
     *
     * @return Database
     *   The singleton Database object.
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
    }

    /**
     * @return MongoClient
     */
    public static function createInstance() {
        return new MongoClient();
    }
}
