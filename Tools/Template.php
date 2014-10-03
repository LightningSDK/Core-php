<?php
/**
 * @file
 * Contains Lightning\Tools\Template
 */

namespace Lightning\Tools;

use Lightning\Tools\Configuration;
use stdClass;

/**
 * The HTML template controller.
 *
 * @package Lightning\Tools
 */
class Template extends Singleton {

    /**
     * The main template file.
     *
     * @var string
     */
    private $template;

    /**
     * The directory where the templates are located.
     *
     * @var string
     */
    private $template_dir;

    /**
     * The page to render.
     *
     * @var string
     */
    private $page;

    /**
     * The variables accessible within the template.
     *
     * @var array
     */
    private $vars;

    /**
     * Initialize the template object.
     */
    public function __construct(){
        $this->template = Configuration::get('template.default');
        $this->template_dir = HOME_PATH . '/' . Configuration::get('template_dir') . '/';
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
    public function __get($var){
        if(isset($this->vars[$var])) {
            return $this->vars[$var];
        } else {
            return null;
        }
    }

    /**
     * Render a template and it's main page content.
     *
     * @param string $page
     *   The page to render within the template.
     * @param bool $return_as_string
     *   When TRUE, the output will be returned instead of output.
     *
     * @return string
     *   The rendered content.
     */
    public function render($page = '', $return_as_string = false){
        if (!$return_as_string) {
            Output::sendCookies();
        }

        extract($this->vars);
        if(!empty($page)) {
            $this->page = $page;
        }

        if ($return_as_string) {
            // Setup an output buffer
            ob_start();
        }

        include $this->template_dir . $this->template . '.tpl.php';

        if ($return_as_string) {
            // Setup an output buffer
            return ob_get_clean();
        }
        exit;
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
    public function set($name, $value){
        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * Copy a list of variables from one object to the template.
     *
     * @param stdClass $object
     *   An object.
     * @param array $vars
     *   A list of variable names to copy.
     */
    public function copy($object, $vars){
        foreach($vars as $v){
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
    public function setReference($name,&$var){
        $this->vars[$name] =& $var;
    }

    /**
     * Set the main template to use when rendering.
     *
     * @param string $template
     *   The main template name excluding .tpl.php.
     */
    public function setTemplate($template){
        $this->template = $template.".tpl.php";
    }

    /**
     * Include a template from within another template.
     *
     * @param string $page
     *   The name of the template excluding .tpl.php.
     */
    public function _include($page){
        extract($this->vars);
        include $this->template_dir.$page.".tpl.php";
    }

    /**
     * Print a question mark with a tool tip.
     *
     * @param $help_string
     * @param string $image
     * @param string $id
     * @param string $class
     * @param null $url
     *
     * @todo this needs JS injection and should be moved to a view.
     */
    public function help($help_string, $image='/images/qmark.png', $id='', $class='', $url=NULL){
        if($url){
            echo "<a href='{$url}'>";
        }
        echo "<img src='{$image}' border='0' class='help {$class}' id='{$id}' />";
        echo "<div class='tooltip'>{$help_string}</div>";
        if($url){
            echo "</a>";
        }
    }
}
