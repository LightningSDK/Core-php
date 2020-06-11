<?php

namespace lightningsdk\core\Filter;

use lightningsdk\core\Model\Mailing\Lists;

class MailingList extends Filter {

    const TYPE = 'select';
    public $display_name = 'Mailing List';

    public function __construct($options) {
        $this->settings = [
            'type' => 'operator_value',
            'field' => 'message_list_id',
            'join' => [
                'join' => 'message_list_user',
                'using' => 'user_id',
            ],
            'options' => [
                'operator' => [
                    'type' => 'select',
                    'options' => [
                        '=' => 'In Mailing List',
                        '!=' => 'Not In Mailing List',
                    ]
                ],
                'value' => [
                    'type' => 'select',
                    'options' => Lists::loadOptions('name'),
                ]
            ]
        ];
    }
}
