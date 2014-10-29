<?

namespace Lightning\Tools;

use Lightning\View\Field;
use Lightning\View\Field\Hidden;

class Form {
    protected $id;
    protected $fields;
    protected $settings;

    public function __construct($id, $fields = array(), $settings = array()) {
        $this->id = $id;
        $this->fields = $fields;
        $this->settings = $settings;
    }

    /**
     * Render the entire form contents.
     *
     * @return string
     *   Fully rendered form HTML.
     *
     * @todo This needs to implement rendering a default table/form
     *   structure for basic form with fields, as well as loading a
     *   custom form template, and elements with custom types.
     */
    public function render() {
        $output = '<form method="post" action="' . (!empty($this->settings['action']) ? $this->settings['action'] : '') . '">';
        foreach ($this->fields as $field) {
            $output .= self::renderTokenInput($field);
        }
        $output = '</form>';
        return $output;
    }

    public function validate() {

    }

    /**
     * Make sure a session is started so there can be a token.
     */
    public static function requiresToken() {
        Session::getInstance(true);
    }

    /**
     * Render a hidden token field.
     *
     * @return string
     *   The full HTML.
     */
    public static function renderTokenInput() {
        return Hidden::render('token', Session::getInstance()->getToken());
    }
}
