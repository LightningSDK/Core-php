<?php

namespace Lightning\Model;

use Lightning\Tools\Database;

class Subscription {
    public static function getLists() {
        return Database::getInstance()->selectIndexed('message_list', 'message_list_id');
    }

    public static function getUserLists($user_id) {
        return Database::getInstance()->selectIndexed(
            array(
                'from' => 'message_list_user',
                'join' => array(
                    'JOIN',
                    'message_list',
                    'USING(message_list_id)',
                )
            ),
            'message_list_id',
            array('user_id' => $user_id)
        );
    }
}
