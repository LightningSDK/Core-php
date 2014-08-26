<?php

namespace Lightning\Tools;

class Random extends Singleton {

    const INT = 1;
    const HEX = 2;
    const BIN = 3;

    protected $engine;

    public function __construct() {
        $this->engine = Configuration::get('random_engine');
    }

    /**
     * @return Random
     */
    public static function getInstance() {
        return parent::getInstance();
    }

    /**
     * @param int $size
     *   The size in bytes.
     * @param int $format
     *
     * @return string|int
     */
    public function get($size = 4, $format = self::INT) {
        // Generate the random data.
        switch ($this->engine) {
            case MCRYPT_DEV_URANDOM:
            case MCRYPT_DEV_RANDOM:
                $random = mcrypt_create_iv($size, $this->engine);
                break;
            default:
                $random = mt_rand();
                break;
        }

        // Format the random data.
        switch ($format) {
            case self::INT;
                return bindec($random);
            case self::BIN;
                return $random;
            case self::HEX;
                return bin2hex($random);
        }
    }
}
