<?php
/**
 * @file
 * Contains Lightning\Tools\Template
 */

namespace Lightning\Tools;

use Lightning\Tools\Cache\Cache;
use Lightning\Tools\Configuration;
use stdClass;

/**
 * The HTML template controller.
 *
 * @package Lightning\Tools
 */
class Template extends Singleton {

    /**
     * Cache settings for specific pages.
     *
     * @var array
     */
    protected $cache = array();

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
    protected $vars = array();

    /**
     * Initialize the template object.
     */
    public function __construct() {
        $this->template = Configuration::get('template.default');
        $this->template_dir = HOME_PATH . '/' . Configuration::get('template_dir') . '/';
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

    /**
     * Render a template and it's main page content.
     *
     * @param string $template
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

        $this->setTemplateMetaData();
        
        if ($return_as_string) {
            return $this->build($template, true);
        } else {
            print $this->build($template, false);
        }
    }

    protected function setTemplateMetaData() {
        foreach (array('title', 'keywords', 'description', 'author') as $meta_data) {
            // Check if template already has these variables set
            $var_name = 'page_'.$meta_data;
            $var = $this->$var_name;
            if (empty($var)) { 
                // Set it by default from configuration if it's not defined earlier
                $this->set('page_' . $meta_data, Configuration::get('meta_data.' . $meta_data));
            }
        }
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
     * @param string $template
     *   The main template name excluding .tpl.php.
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    public function setCache($page, $ttl = null, $size = null) {
        $ttl = $ttl ?: (Configuration::get('page.cache.ttl')) ?: Cache::MONTH;
        $size = $size ?: (Configuration::get('page.cache.size')) ?: Cache::MEDIUM;
        $this->cache[$page] = array(
            'ttl' => $ttl,
            'size' => $size,
        );
    }

    /**
     * Build a template file, optionally with caching.
     *
     * @param string $template
     *   The name of the template excluding .tpl.php.
     * @param boolean $return_as_string
     *   Whether to return the contents or print them to the stdout.
     *
     * @return string|null
     *   The output if requested, or null.
     */
    public function build($template, $return_as_string = false) {
        if (!empty($this->cache[$template])) {
            // Cache is enabled for this page.
            $ttl = !empty($this->cache[$template]['ttl']) ? $this->cache[$template]['ttl'] : Cache::MONTH;
            $size = !empty($this->cache[$template]['size']) ? $this->cache[$template]['size'] : Cache::MEDIUM;

            // Load the cache.
            $cache = Cache::get('template_' . $template, $ttl, $size);

            // If the cache doesn't exist.
            if ($cache->isNew()) {
                // Build the page.
                $cache->value = $this->_include($template, true);
            }

            // Return or output.
            if ($return_as_string) {
                return $cache->value;
            } else {
                print $cache->value;
            }
        } else {
            // Return or output without cache.
            return $this->_include($template, $return_as_string);
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

        // Include the file with vars in scope.
        extract($this->vars);
        include $this->template_dir . $template . '.tpl.php';

        if ($return_as_string) {
            return ob_get_clean();
        }
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
    public function help($help_string, $image = '/images/qmark.png', $id = '', $class = '', $url = NULL) {
        if ($url) {
            echo "<a href='{$url}'>";
        }
        echo "<img src='{$image}' border='0' class='help {$class}' id='{$id}' />";
        echo "<div class='tooltip'>{$help_string}</div>";
        if ($url) {
            echo "</a>";
        }
    }
}
