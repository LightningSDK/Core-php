<?php

namespace Lightning\Model;

use Lightning\Model\BaseObject;
use Lightning\Tools\Database;
use Lightning\Tools\ClientUser;

class SocialAuthOverridable extends BaseObject {
    const TABLE = 'social_auth';
    const PRIMARY_KEY = 'social_auth_id';

    public static function getAuthorizations() {
        return Database::getInstance()->selectAll('social_auth', ['user_id' => ClientUser::getInstance()->id]);
    }

    public function save() {
        // Delete any other instances first.
        Database::getInstance()->delete('social_auth', [
            'user_id' => $this->user_id,
            'social_id' => $this->social_id,
            'network' => $this->network,
        ]);

        parent::save();
    }
}
