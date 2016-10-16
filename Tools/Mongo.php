<?php

namespace Lightning\Tools;

use Lightning\Tools\Configuration;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;

class Mongo {

    protected $connection;
    protected $database;
    protected $collection;
    protected static $connections = [];

    protected function __construct($uri) {
        $this->connection = new Manager($uri);
        return $this;
    }

    /**
     * Get the default database instance.
     *
     * @return \MongoDB\Driver\Manager
     *   The singleton Database object.
     */
    public static function getInstance($server = 'default') {
        return static::$connections[$server];
    }

    /**
     * @return Mongo
     */
    public static function getConnection($database, $collection, $server = 'default') {
        if (empty(static::$connections[$server])) {
            static::$connections[$server] = new static(Configuration::get('mongo.' . $server));
        }
        static::$connections[$server]->setDatabase($database);
        static::$connections[$server]->setCollection($collection);
        return static::$connections[$server];
    }

    public function setDatabase($database) {
        $this->database = $database;
    }

    public function setCollection($collection) {
        $this->collection = $collection;
    }

    public function update($filter, $set, $options = []) {
        $query = new BulkWrite();
        $query->update($filter, $set, $options);
        $this->connection->executeBulkWrite($this->database . '.' . $this->collection, $query);
    }

    public function bulk($query) {
        $this->connection->executeBulkWrite($this->database . '.' . $this->collection, $query);
    }
}
