<?php

namespace Lightning\Tools;

use MongoClient;
use MongoCollection;

class Mongo extends Singleton {

    /**
     * Get the default database instance.
     *
     * @return MongoClient
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

    /**
     * @param $database
     * @param $collection
     *
     * @return MongoCollection
     */
    public static function getConnection($database, $collection) {
        return self::getInstance()->selectDB($database)->selectCollection($collection);
    }
}
