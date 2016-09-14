<?php

namespace Lightning\View;

class TablePresets {
    public static function userSearch() {
        return [
            'autocomplete' => [
                'table' => 'user',
                'field' => 'user_id',
                'search' => ['email', 'first', 'last'],
                'display_value' => function(&$row) {
                    $row = $row['first'] . ' ' . $row['last'] . '(' . $row['email'] . ')';
                }
            ],
        ];
    }
}
