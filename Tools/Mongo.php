<?php

namespace Lightning\Tools;

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;

class Mongo extends Singleton {

    /**
     * @var Manager
     */
    protected $connection;

    /**
     * Mongo constructor.
     * @param Manager $connection
     */
    protected function __construct($connection) {
        $this->connection = $connection;
    }

    /**
     * Get the default database instance.
     *
     * @return Mongo
     *   The singleton Database object.
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
    }

    /**
     * @return Mongo
     * @throws Manager
     */
    public static function createInstance() {
        return new static(new Manager("mongodb://localhost:27017/"));
    }

    /**
     * @param string $database
     * @param string $collection
     *
     * @return Collection
     */

    /**
     * @param $database
     * @param $collection
     * @return mixed
     */
    public static function getConnection($database, $collection) {
        return self::getInstance()->connection->$database->$collection;
    }

    public function queryArray($query) {
        if (empty($query['from'])) {
            throw new \Exception('Missing database and collection');
        }

        $options = [];

        if (!empty($query['sort'])) {
            $sort = $query['sort'];
            foreach ($sort as &$s) {
                switch ($s) {
                    case 'ASC':  $s = 1;  break;
                    case 'DESC': $s = -1; break;
                }
            }
            $options['sort'] = $sort;
        }

        $mq = new \MongoDB\Driver\Query($query['where'], $options);
        return $this->connection->executeQuery($query['from'], $mq);
    }

    public function insert($collection, $values) {
        $write = new BulkWrite();
        $write->insert($values);
        return $this->connection->executeBulkWrite($collection, $write);
    }
}
