<?php
/**
 * @file
 * Lightning\Pages\Mailing\Messages
 */

namespace Lightning\Pages\Mailing;

use Lightning\Model\Permissions;
use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Database;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\JS;

/**
 * A page handler for editing bulk mailer messages.
 *
 * @package Lightning\Pages\Mailing
 */
class Messages extends Table {

    const TABLE = 'message';
    const PRIMARY_KEY = 'message_id';

    protected $table = 'message';

    protected $preset = [
        'never_resend' => [
            'type' => 'checkbox',
            'default' => 1,
        ],
        'template_id' => [
            'type' => 'lookup',
            'item_display_name' => 'title',
            'lookuptable'=>'message_template',
            'display_column'=>'title',
            'edit_only'=>true
        ],
        'send_date' => [
            'type' => 'datetime',
            'allow_blank' => true,
        ],
        'body' => [
            'type' => 'html',
            'editor' => 'full',
            'upload' => true,
            'url' => 'full',
            'trusted' => true,
        ]
    ];

    protected $links = [
        'message_criteria' => [
            'list' => true,
            'index' => 'message_message_criteria',
            'key' => 'message_criteria_id',
            'option_name' => 'criteria_name',
            'display_name' => 'Criteria',
            'display_column' => 'criteria_name',
        ],
        'message_list' => [
            'list' => true,
            'index' => 'message_message_list',
            'key' => 'message_list_id',
            'option_name' => 'name',
            'display_name' => 'Lists',
            'display_column' => 'name',
        ],
    ];

    protected $action_fields = [
        'stats' => [
            'type' => 'link',
            'url' => '/admin/mailing/stats?message_id=',
            'display_name' => 'Stats',
            'display_value' => '<img src="/images/lightning/chart.png" border="0">',
        ],
        'send' => [
            'type' => 'link',
            'url' => '/admin/mailing/send?id=',
            'display_name' => 'Send',
            'display_value' => '<img src="/images/lightning/new_message.png" border="0">',
        ],
    ];

    protected $custom_buttons = [
        'send' => [
            'type' => self::CB_SUBMITANDREDIRECT,
            'text' => 'Update &amp; Send',
            'redirect' => '/admin/mailing/send?id={' . self::PRIMARY_KEY . '}',
        ],
    ];

    protected $sort = ['message_id' => 'DESC'];
    protected $duplicatable = true;

    /**
     * Require admin privileges.
     */
    public function hasAccess() {
        return ClientUser::requirePermission(Permissions::EDIT_MAIL_MESSAGES);
    }

    public function __construct() {
        parent::__construct();

        $action = Request::get('action');
        if ($action == 'edit' || $action == 'new') {
            JS::startup('
                lightning.admin.messageEditor.checkVars();
                $("#add_message_criteria_button").click();
            ');
            JS::set('table.linkProcess.cirteria', 'lightning.admin.messageEditor.checkVars');
        }
    }

    public function afterPost() {
        $db = Database::getInstance();

        // Find all the criteria added to this message
        $criteria_list = $db->select(
            [
                'from' => 'message_message_criteria',
                'join' => [
                    'JOIN',
                    'message_criteria',
                    'USING (message_criteria_id)',
                ],
            ],
            ['message_id' => $this->id]
        );

        // See if any variables have been set.
        foreach($criteria_list as $c) {
            // If the criteria requires variables.
            if (!empty($c['variables'])) {
                // See what variables are required.
                $vars = explode(',', $c['variables']);
                $var_data = [];
                foreach($vars as $v) {
                    $var_data[$v] = Request::post('var_' . $c['message_criteria_id'] . '_' . $v);
                }
                $db->update(
                    'message_message_criteria',
                    ['field_values' => json_encode($var_data)],
                    [
                        'message_id' => Request::post('id', 'int'),
                        'message_criteria_id' => $c['message_criteria_id'],
                    ]
                );
            }
        }
    }

    public function getFields() {
        $cl = Request::get('criteria_list', 'explode', 'int');
        $output = [];
        if (!empty($cl)) {
            $fields = Database::getInstance()->select('message_criteria', ['message_criteria_id' => ['IN', $cl]]);
            foreach($fields as $f) {
                if (!empty($f['variables'])) {
                    $values = Database::getInstance()->selectRow(
                        'message_message_criteria',
                        [
                            'message_id' => Request::get('message_id', 'int'),
                            'message_criteria_id' => $f['message_criteria_id'],
                        ]
                    );
                    $output[] = [
                        'criteria_id' => $f['message_criteria_id'],
                        'variables' => explode(',',$f['variables']),
                        'values' => json_decode($values['field_values']),
                    ];
                }
            }
        }

        Output::json(['criteria' => $output]);
    }
}
