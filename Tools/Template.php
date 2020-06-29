<?php
/**
 * @file
 * Contains lightningsdk\core\Tools\Template
 */

namespace lightningsdk\core\Tools;

use Exception;
use lightningsdk\core\View\CSS;
use lightningsdk\core\View\JS;
use stdClass;

/**
 * The HTML template controller.
 *
 * @package lightningsdk\core\Tools
 */
class Template extends Singleton {

    /**
     * Whether to output debug data.
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * Footer html content.
     *
     * @var string
     */
    protected $footer = '';

    /**
     * Header html content.
     *
     * @var string
     */
    protected $header = '';

    /**
     * The main template file.
     *
     * @var string
     */
    protected $template;

    /**
     * The directory where the templates are located.
     *
     * @var string
     */
    protected $template_dir;

    /**
     * The variables accessible within the template.
     *
     * @var array
     */
    protected $vars = [];

    /**
     * Initialize the template object.
     */
    public function __construct() {
        $this->template = Configuration::get('template.default');
        $this->template_dir = HOME_PATH . '/' . Configuration::get('template_dir') . '/';
        if (Configuration::get('debug')) {
            $this->debug = true;
        }
    }

    public function setDirectory($path) {
        if (Request::isCLI()) {
            $this->template_dir = $path;
        } else {
            throw new \Exception('Can not set template path - security exception');
        }
    }

    /**
     * Get the template instance.
     *
     * @param boolean $create
     *   Whether to create the instance if it doesn't exist.
     *
     * @return Template
     *   The template object.
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
    }

    /**
     * Get a variable stored in the template.
     *
     * @param string $var
     *   The name of the variable.
     *
     * @return mixed
     *   The variable's value.
     */
    public function __get($var) {
        if (isset($this->vars[$var])) {
            return $this->vars[$var];
        } else {
            return null;
        }
    }

    public function __isset($var) {
        return isset($this->vars[$var]);
    }

    public function setData($vars) {
        $this->vars = $vars;
    }

    /**
     * Render a template and it's main page content.
     *
     * @param string|array $template
     *   The main template to render within the template.
     * @param bool $return_as_string
     *   When TRUE, the output will be returned instead of output.
     *
     * @return string
     *   The rendered content.
     */
    public function render($template = null, $return_as_string = false) {
        if (!$return_as_string) {
            Output::sendCookies();
        }

        // Get the default template if none is supplied.
        if (empty($template)) {
            $template = $this->template;
        }

        return $this->build($template, $return_as_string);
    }

    /**
     * Markup renderer.
     *
     * This renders a template that is included in another template using {{template name="some/template"}}
     *
     * @param array $options
     * @param array $vars
     *
     * @return string
     */
    public static function renderMarkup($options, $vars) {
        $sub_template = new Template();
        if (!empty($options['module'])) {
            $template = [$options['name'], $options['module']];
        } else {
            $template = $options['name'];
        }
        $sub_template->setData($vars);
        return $sub_template->render($template, true);
    }

    /**
     * Set a variable so it's accessible within the template.
     *
     * @param string $name
     *   The variable name.
     * @param $value
     *   The variable value.
     *
     * @return Template
     *   Return self for function chaining.
     */
    public function set($name, $value) {
        $this->vars[$name] = $value;
        return $this;
    }

    public function setDebug($value = true) {
        $this->debug = $value;
    }

    /**
     * Copy a list of variables from one object to the template.
     *
     * @param stdClass $object
     *   An object.
     * @param array $vars
     *   A list of variable names to copy.
     */
    public function copy($object, $vars) {
        foreach($vars as $v) {
            $this->set($v, $object->get($v));
        }
    }

    /**
     * Add a variable by reference.
     *
     * @param string $name
     *   The variable name.
     * @param mixed $var
     *   The variable value.
     */
    public function setReference($name,&$var) {
        $this->vars[$name] =& $var;
    }

    /**
     * Set the main template to use when rendering.
     *
     * @param array|string $template
     *   The main template name excluding .tpl.php.
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    /**
     * Add footer content to output before closing body tag.
     *
     * @param string $content
     */
    public function addFooter($content) {
        $this->footer .= $content;
    }

    public function addHeader($content) {
        $this->header .= $content;
    }

    public function renderHeader() {
        return JS::render() . CSS::render() . $this->header;
    }

    /**
     * Display the footer content.
     */
    public function renderFooter() {
        echo JS::render() . CSS::render() . $this->footer;
        if (Configuration::get('debug') || ClientUser::getInstance()->isAdmin()) {
            echo '<div id="performance-data"><ul class="accordion" data-accordion>';
            echo '<li class="accordion-item is-active" data-accordion-item>';
            echo '<a href="#" class="accordion-title">Performance Overview</a>';
            echo '<div class="accordion-content" data-tab-content>';
            echo '<pre class="debug">';
            echo json_encode(Performance::timeReport(), JSON_PRETTY_PRINT);
            echo '</pre>';
            echo '</div>';
            echo '</li>';
            echo '<li class="accordion-item" data-accordion-item>';
            echo '<a href="#" class="accordion-title">Database Queries</a>';
            echo '<div class="accordion-content" data-tab-content>';
            echo '<pre class="debug">';
            $database = Database::getInstance();
            echo json_encode($database->getQueries(), JSON_PRETTY_PRINT);
            echo '</pre>';
            echo '</div>';
            echo '</li>';
            echo '</ul></div>';
        }
    }

    /**
     * Populate a template.
     *
     * @param array|string $template
     *   The name of the template excluding .tpl.php.
     * @param boolean $return_as_string
     *   Whether to return the contents or print them to the stdout.
     *
     * @return string|null
     *   The output if requested, or null.
     */
    public function build($template, $return_as_string = false) {
        $value = $this->_include($template, true);

        if ($this->debug) {
            // Wrap template with debug information.
            $value = '<!-- START TEMPLATE ' . json_encode($template) . '.tpl.php -->' . $value . '<!-- END TEMPLATE ' . json_encode($template) . '.tpl.php -->';
        }

        // Return or output.
        if ($return_as_string) {
            return $value;
        } else {
            print $value;
            return '';
        }
    }

    /**
     * @param string|array $template
     *   If this is a string, it will be found in Source/Templates/{string}.tpl.php
     *   If this is an array, it will be found in Modules/[0]/Templates/[1].tpl.php
     *
     * @return string
     */
    protected function getFileName($template) {
        if (is_string($template)) {
            return $this->template_dir . $template;
        }
        elseif (is_array($template)) {
            if ($template[1] == 'Lightning') {
                return HOME_PATH . '/Lightning/Templates/' . $template[0];
            } else {
                foreach(['Modules', 'vendor'] as $path) {
                    if (file_exists(HOME_PATH . '/' . $path . '/' . $template[1] . '/Templates/' . $template[0] . '.tpl.php')) {
                        return HOME_PATH . '/' . $path . '/' . $template[1] . '/Templates/' . $template[0];
                    }
                }
            }
            throw new \Exception('template not found: ' . json_encode($template));
        }
    }

    /**
     * @param string $template
     *   The name of the template file to render.
     * @param boolean $return_as_string
     *   Whether to return the contents or print them to the stdout.
     *
     * @return string|null
     *   The output if requested, or null.
     */
    protected function _include($template, $return_as_string = false) {
        if ($return_as_string) {
            ob_start();
        }

        $error = '';

        try {
            // Include the file with vars in scope.
            extract($this->vars);
            include $this->getFileName($template) . '.tpl.php';
        } catch (Exception $e) {
            Logger::exception($e);
            $error = 'Error rendering template!';
        }

        if ($return_as_string) {
            return ob_get_clean() . $error;
        } else {
            echo $error;
        }
    }
}
