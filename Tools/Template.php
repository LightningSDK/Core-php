<?php

namespace Lightning\Tools;

use Lightning\Tools\Configuration;

/**
 * Class Template
 *
 * The HTML template controller.
 */
class Template extends Singleton {

    private $template = 'template.tpl.php';
    private $template_dir;
    private $page;
    private $vars;

    /**
     * Initialize the template object.
     */
    public function __construct(){
        $this->template_dir = HOME_PATH . '/Source/Templates/';
//    if(Configuration::get('user_mobile')){
//      require_once HOME_PATH . '/include/class_mobile.php';
//      $detect = new \Mobile_Detect();
//      if ($detect->isMobile()){
//        $this->template = 'template_mobile.tpl.php';
//        $this->assign("mobile",true);
//      }
//    }
    }

    public function __get($var){
        if(isset($this->vars[$var]))
            return $this->vars[$var];
        else
            return NULL;
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
    public function render($page = "", $return_as_string = FALSE){
        extract($this->vars);
        if(!empty($page)) {
            $this->page = $page;
        }

        if ($return_as_string) {
            // Setup an output buffer
            ob_start();
        }

        include $this->template_dir . $this->template;

        if ($return_as_string) {
            // Setup an output buffer
            return ob_get_clean();
        }
        exit;
    }

    public function set_page($page = ""){
        $this->page = $page;
    }

    public function set_default_page($page) {
        if (empty($this->page)) {
            $this->page = $page;
        }
    }

    public function set($name,$var){
        $this->vars[$name] = $var;
        return $this;
    }

    public function copy($object, $vars){
        foreach($vars as $v){
            $this->set($v, $object->get($v));
        }
    }

    public function setReference($name,&$var){
        $this->vars[$name] =& $var;
    }

    public function setTemplate($template){
        $this->template = $template.".tpl.php";
    }

    public function _include($page){
        extract($this->vars);
        include $this->template_dir.$page.".tpl.php";
    }

    // THIS FUNCTION WILL PRINT A QUESTION MARK WITH A TOOL TIP

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


    // SPECIAL FORM FIELDS

    function get_date($field){
        if($_POST[$field.'_m'] != '' && $_POST[$field.'_d'] != '' && $_POST[$field.'_y'] != '')
            return gregoriantojd($_POST[$field.'_m'], $_POST[$field.'_d'], $_POST[$field.'_y']);
        else
            return 0;
    }

    function get_time($field){
        if($_POST[$field.'_h'] != '')
            $time = ($_POST[$field.'_h']*60)+$_POST[$field.'_m']+(($_POST[$field.'_a']=="PM") ? 720 : 0);
        else
            $time = -1;
        if($time > 1440) $time -= 1440;
        return $time;
    }

    function today(){
        return gregoriantojd (date("m"),date("d"),date("Y"));
    }
}
