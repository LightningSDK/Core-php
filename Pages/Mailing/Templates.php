<?

namespace Lightning\Pages\Mailing;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;

class Templates extends Table {

    public function __construct() {
        ClientUser::requireAdmin();
        parent::__construct();
    }

    protected $table = 'message_template';
    protected $preset = array(
        'body' => array(
            'type' => 'html',
            'allowed_html' => 'body,html,style',
            'allowed_css' => '*',
            'trusted' => true,
        )
    );
}
