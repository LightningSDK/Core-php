<?php

namespace Lightning\Tools\Security;

use Lightning\Tools\Configuration;
use Lightning\Tools\Singleton;

class RandomOverridable extends Singleton {

    const INT = 1;
    const HEX = 2;
    const BIN = 3;
    const BASE64 = 4;
    const LONG = 5;

    protected static $engine = null;

    /**
     * @return Random
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
    }

    protected static function getEngine() {
        if (self::$engine == null) {
            self::$engine = Configuration::get('random_engine');
        }
        return self::$engine;
    }

    /**
     * @param int $size
     *   The size in bytes.
     * @param int $format
     *
     * @return string|int
     *
     * @throws \Exception if random gen fails
     */
    public static function get($size = 4, $format = self::INT) {
        // Generate the random data.
        $random = random_bytes($size);

        // Format the random data.
        switch ($format) {
            case self::INT:
                $val = unpack('I', $random);
                return $val[1];
            case self::LONG:
                $val = unpack('L', $random);
                return $val[1];
            case self::BIN:
                return $random;
            case self::HEX:
                return bin2hex($random);
            case self::BASE64:
                return base64_encode($random);
        }
    }
}
