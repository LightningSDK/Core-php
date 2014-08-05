<?

namespace Lightning\Tools;

use Lightning\View\Field;

class Form {
    protected $id;
    protected $fields;
    protected $settings;

    public function __construct($id, $fields = array(), $settings = array()) {
        $this->id = $id;
        $this->fields = $fields;
        $this->settings = $settings;
    }

    public function render() {
        $output = '<form method="post" action="' . (!empty($this->settings['action']) ? $this->settings['action'] : '') . '">';
        foreach ($this->fields as $field) {
            $output .= Field::render($field);
        }
        $output = '</form>';
    }

    public function validate() {

    }

    public function submit() {

    }
}
