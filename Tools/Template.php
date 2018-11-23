<?php
/**
 * @file
 * Contains Lightning\Tools\Template
 */

namespace Lightning\Tools;

use Lightning\Tools\Cache\Cache;
use Lightning\View\CSS;
use Lightning\View\JS;
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
    protected $cache = [];

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
     * @param string $template
     *   The main template name excluding .tpl.php.
     */
    public function setTemplate($template) {
        $this->template = $template;
    }

    public function setCache($page, $ttl = null, $size = null) {
        $ttl = $ttl ?: (Configuration::get('page.cache.ttl')) ?: Cache::MONTH;
        $size = $size ?: (Configuration::get('page.cache.size')) ?: Cache::MEDIUM;
        $this->cache[$page] = [
            'ttl' => $ttl,
            'size' => $size,
        ];
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
        if (ClientUser::getInstance()->isAdmin()) {
            echo '<pre class="debug">';
            $database = Database::getInstance();
            print_r(Performance::timeReport());
            print_r($database->getQueries());
            echo '</pre>';
        }
    }

    /**
     * Build a template file, optionally with caching.
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
        $cache_name = $this->getCacheName($template);
        if (!empty($this->cache[$cache_name])) {
            // Cache is enabled for this page.
            $ttl = !empty($this->cache[$cache_name]['ttl']) ? $this->cache[$cache_name]['ttl'] : Cache::MONTH;
            $size = !empty($this->cache[$cache_name]['size']) ? $this->cache[$cache_name]['size'] : Cache::MEDIUM;

            // Load the cache.
            $cache = Cache::get('template_' . $this->getCacheName($cache_name), $ttl, $size);

            // If the cache doesn't exist.
            if ($cache->isNew()) {
                // Build the page.
                $cache->value = $this->_include($template, true);
            }

            $value = $cache->value;
        } else {
            // Return or output without cache.
            $value = $this->_include($template, true);
        }

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

    protected function getCacheName($template) {
        return preg_replace('|\\\\|', '__', $this->getFileName($template));
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
                return HOME_PATH . '/Modules/' . $template[1] . '/Templates/' . $template[0];
            }
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
        include $this->getFileName($template) . '.tpl.php';

        if ($return_as_string) {
            return ob_get_clean();
        }
    }
}
