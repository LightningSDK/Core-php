<?php
/**
 * @file
 * Lightning\Pages\Mailing\Messages
 */

namespace Lightning\Pages\Mailing;

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

    protected $table = 'message';
    protected $preset = array(
        'never_resend' => array(
            'type' => 'checkbox',
        ),
        'template_id' => array(
            'type' => 'lookup',
            'item_display_name' => 'title',
            'lookuptable'=>'message_template',
            'display_column'=>'title',
            'edit_only'=>true
        ),
        'send_date' => array(
            'type' => 'datetime',
            'allow_blank' => true,
        ),
        'body' => array(
            'type' => 'html',
            'editor' => 'full',
            'upload' => true,
            'full_page' => true,
        )
    );

    protected $links = array(
        'message_criteria' => array(
            'list' => true,
            'index' => 'message_message_criteria',
            'key' => 'message_criteria_id',
            'option_name' => 'criteria_name',
            'display_name' => 'Criteria',
            'display_column' => 'criteria_name',
        ),
        'message_list' => array(
            'list' => true,
            'index' => 'message_message_list',
            'key' => 'message_list_id',
            'option_name' => 'name',
            'display_name' => 'Lists',
            'display_column' => 'name',
        ),
    );

    protected $action_fields = array(
        'stats' => array(
            'type' => 'link',
            'url' => '/admin/mailing/stats?message_id=',
            'display_value' => 'Stats',
        ),
        'send' => array(
            'type' => 'link',
            'url' => '/admin/mailing/send?id=',
            'display_value' => '<img src="/images/main/new_message.png" border="0">',
        ),
    );

    protected $custom_buttons = [
        'send' => [
            'type' => self::CB_SUBMITANDREDIRECT,
            'text' => 'Update &amp; Send',
            'redirect' => '/admin/mailing/send?id={ID}',
        ],
    ];
    
    protected $sort = 'message_id DESC';
    protected $maxPerPage = 100;

    /**
     * Require admin privileges.
     */
    public function hasAccess() {
        ClientUser::requireAdmin();
        return true;
    }

    public function __construct() {
        parent::__construct();

        $action = Request::get('action');
        if ($action == 'edit' || $action == 'new') {
            JS::startup('
                lightning.admin.messageEditor.checkVars();
                $("#add_message_criteria_button").click(lightning.admin.messageEditor.checkVars);
            ');
        }

        $this->post_actions['after_post'] = function() {
            $db = Database::getInstance();

            // Find all the criteria added to this message
            $criteria_list = $db->select(
                array(
                    'from' => 'message_message_criteria',
                    'join' => array(
                        'JOIN',
                        'message_criteria',
                        'USING (message_criteria_id)',
                    ),
                ),
                array('message_id' => $this->id)
            );

            // See if any variables have been set.
            foreach($criteria_list as $c){
                // If the criteria requires variables.
                if(!empty($c['variables'])){
                    // See what variables are required.
                    $vars = explode(',', $c['variables']);
                    $var_data = array();
                    foreach($vars as $v) {
                        $var_data[$v] = Request::post('var_' . $c['message_criteria_id'] . '_' . $v);
                    }
                    $db->update(
                        'message_message_criteria',
                        array('field_values' => json_encode($var_data)),
                        array(
                            'message_id' => Request::post('id', 'int'),
                            'message_criteria_id' => $c['message_criteria_id'],
                        )
                    );
                }
            }
        };
    }

    public function getFields() {
        // TODO: REQUIRE ADMIN
        $cl = Request::get('criteria_list', 'explode', 'int');
        $output = array();
        if (!empty($cl)) {
            $fields = Database::getInstance()->select('message_criteria', array('message_criteria_id' => array('IN', $cl)));
            foreach($fields as $f){
                if(!empty($f['variables'])){
                    $values = Database::getInstance()->selectRow(
                        'message_message_criteria',
                        array(
                            'message_id' => Request::get('message_id', 'int'),
                            'message_criteria_id' => $f['message_criteria_id'],
                        )
                    );
                    $output[] = array(
                        'criteria_id' => $f['message_criteria_id'],
                        'variables' => explode(',',$f['variables']),
                        'values' => json_decode($values['field_values']),
                    );
                }
            }
        }

        Output::json(array('criteria' => $output));
    }
}
