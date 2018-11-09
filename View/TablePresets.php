<?php

namespace Lightning\View;

use Lightning\Model\User;

class TablePresets {
    public static function userSearch($field_name = 'user_id') {
        return [
            'autocomplete' => [
                'table' => 'user',
                'field' => 'user_id',
                'search' => ['email', 'first', 'last'],
                'display_value' => function(&$row) {
                    $row = $row['first'] . ' ' . $row['last'] . '(' . $row['email'] . ')';
                }
            ],
            'display_value' => function($row) use ($field_name) {
                if (empty($row[$field_name])) {
                    return '';
                }
                $user = User::loadById($row[$field_name]);
                return $user->first . ' ' . $user->last . '(' . $user-> email . ':' . $row[$field_name] . ')';
            }
        ];
    }
}
