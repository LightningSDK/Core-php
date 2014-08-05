<?php

namespace Lightning\Tools;

use Lightning\Tools\Singleton;
use Lightning\Model\User;

/**
 * Class User
 *
 * A singleton for the global user.
 */
class ClientUser extends Singleton {

    /**
     * Create the default logged in user.
     */
    public static function createInstance() {
        return new User();
    }
}
