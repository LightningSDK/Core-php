<?php

/**
 * Cache controller for Redis.
 *
 * Based on https://github.com/ptrofimov/tinyredisclient
 */
namespace lightningsdk\core\Tools\Cache;

use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Output;

class Redis extends CacheController implements CacheControllerInterface {

    const NEW_ONLY = 'NX';
    const UPDATE_ONLY = 'XX';

    protected $socket;

    /**
     * @var \Redis
     */
    protected $connection;

    public function __construct($settings = []) {
        $this->socket = Configuration::get('redis.socket');
        if (empty($this->socket)) {
            Output::error('Redis not configured');
        }
        parent::__construct($settings);
    }

    public function __destruct() {
        // Close the socket.
        if (!empty($this->connection)) {
            $this->connection->close();
        }
    }

    public function get($key, $default = null) {
        $this->connect();

        if ($result = $this->connection->get($this->getKey($key))) {
            return unserialize($result);
        } else {
            return $default;
        }
    }

    public function set($key, $value, $ttl = null, $NXXX = '') {
        $this->connect();

        if (empty($ttl)) {
            $ttl = Configuration::get('redis.default_ttl');
        }

        if (empty($ttl)) {
            return $this->connection->set($this->getKey($key), serialize($value));
        } else {
            return $this->connection->set($this->getKey($key), serialize($value), $ttl);
        }
    }

    public function unset($key) {
        $this->connect();
        $this->connection->del($this->getKey($key));
    }

    protected function connect() {
        if (empty($this->connection)) {
            $this->connection = new \Redis();
            $this->connection->connect($this->socket);
        }
    }

    protected function getKey($key) {
        static $prefix;
        if ($prefix == null) {
            $prefix = Configuration::get('redis.prefix', '');
        }
        return $prefix . $key;
    }
}
