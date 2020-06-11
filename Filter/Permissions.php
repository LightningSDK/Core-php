<?php

namespace lightningsdk\core\Filter;

class Permissions extends Filter {

    const TYPE = 'select';
    public $display_name = 'Permissions';

    public function __construct($options) {
        $this->settings = [
            'type' => 'operator_value',
            'field' => 'permission_id',
            'field_table' => 'role_permission',
            'join' => [
                [
                    'join' => 'user_role',
                    'using' => 'user_id',
                ], [
                    'join' => 'role_permission',
                    'using' => 'role_id',
                ],
            ],
            'options' => [
                'operator' => [
                    'type' => 'select',
                    'options' => [
                        '=' => 'Has Permission',
                        '!=' => 'Doesn\'t Have Permission',
                    ]
                ],
                'value' => [
                    'type' => 'select',
                    'options' => \lightningsdk\core\Model\Permissions::loadOptions('name'),
                ]
            ]
        ];
    }
}
