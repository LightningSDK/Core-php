<?

namespace Lightning\Pages\Mailing;

use Lightning\Pages\Table;
use Lightning\Tools\ClientUser;
use Lightning\View\JS;

class Templates extends Table {

    public function __construct() {
        ClientUser::requireAdmin();
        parent::__construct();
    }

    protected $table = 'message_template';
    protected $preset = array(
        'body' => array(
            'type' => 'html',
            'full_page' => true,
        )
    );
}
