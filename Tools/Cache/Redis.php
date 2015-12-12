<?php

/**
 * Cache controller for Redis.
 *
 * Based on https://github.com/ptrofimov/tinyredisclient
 */
namespace Lightning\Tools\Cache;

use Exception;
use Lightning\Tools\Configuration;
use Lightning\Tools\Singleton;

class Redis extends Singleton {

    const NEW_ONLY = 'NX';
    const UPDATE_ONLY = 'XX';

    protected $socket;
    protected $connection;

    public function __construct($socket) {
        $this->socket = $socket;
    }

    public function __destruct() {
        // Close the socket.
        fclose($this->connection);
    }

    public static function createInstance($socket = null) {
        if (empty($socket)) {
            $socket = Configuration::get('redis.socket');
        }
        return new static($socket);
    }

    public function get($key) {
        return $this->send('GET', $key);
    }

    public function set($key, $value, $ttl = null, $NXXX = '') {
        if (empty($ttl)) {
            $ttl = Configuration::get('redis.default_ttl');
        }
        $params = ['SET', $key, $value];

        // @todo: This causes an error.
//        if (!empty($ttl)) {
//            if (!preg_match('|^[EP]X |', $ttl)) {
//                $ttl = 'EX ' . $ttl;
//            }
//            $params[] = $ttl;
//        }

        if (!empty($NXXX)) {
            $params[] = $NXXX;
        }
        return $this->send($params);
    }

    public function delete() {
        $args = func_get_args();
        array_unshift($args, 'DEL');
        return $this->send($args);
    }

    public function send($args) {
        if (!is_array($args)) {
            $args = func_get_args();
        }

        if (empty($this->connection)) {
            $this->connection = stream_socket_client($this->socket);
        }

        $cmd = '*' . count($args) . "\r\n";
        foreach ($args as $item) {
            $cmd .= '$' . strlen($item) . "\r\n" . $item . "\r\n";
        }
        fwrite($this->connection, $cmd);

        return $this->getResponse();
    }

    public function getResponse() {
        $line = fgets($this->connection);
        list($type, $result) = array($line[0], substr($line, 1, strlen($line) - 3));
        if ($type == '-') { // error message
            throw new Exception($result);
        } elseif ($type == '$') { // bulk reply
            if ($result == -1) {
                $result = null;
            } else {
                $line = fread($this->connection, $result + 2);
                $result = substr($line, 0, strlen($line) - 2);
            }
        } elseif ($type == '*') { // multi-bulk reply
            $count = ( int ) $result;
            for ($i = 0, $result = array(); $i < $count; $i++) {
                $result[] = $this->getResponse();
            }
        }
        return $result;
    }
}
