<?php

namespace lightningsdk\core\Filter;

use lightningsdk\core\Model\Role;

class Roles extends Filter {

    const TYPE = 'select';
    public $display_name = 'Roles';

    public function __construct($options) {
        $this->settings = [
            'type' => 'operator_value',
            'field' => 'role_id',
            'field_table' => 'user_role',
            'join' => [
                [
                    'join' => 'user_role',
                    'using' => 'user_id',
                ]
            ],
            'options' => [
                'operator' => [
                    'type' => 'select',
                    'options' => [
                        '=' => 'Has Role',
                        '!=' => 'Doesn\'t Have Role',
                    ]
                ],
                'value' => [
                    'type' => 'select',
                    'options' => Role::loadOptions('name'),
                ]
            ]
        ];
    }
}
