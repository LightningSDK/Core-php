<?php

namespace Overridable\Lightning\Model;

use Lightning\Model\Object;
use Lightning\Tools\Database;

class SocialAuth extends Object {
    const TABLE = 'social_auth';
    const PRIMARY_KEY = 'social_auth_id';

    public static function getAuthorizations() {
        return Database::getInstance()->selectAll('social_auth');
    }
}
