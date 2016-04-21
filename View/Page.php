<?php

namespace Overridable\Lightning\View;

use Exception;
use Lightning\Model\Blog;
use Lightning\Tools\Configuration;
use Lightning\Tools\Language;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\CSS;
use Lightning\View\JS;
use Lightning\Model\Page as PageModel;

/**
 * The basic html page handler.
 *
 * @package Overridable\Lightning\View
 * @todo: Should be abstract
 */
class Page {

    /**
     * The template file.
     *
     * @var string
     */
    protected $template;

    /**
     * Whether to ignore missing or invalid tokens on post requests.
     *
     * @var boolean
     */
    protected $ignoreToken = false;

    /**
     * The current highlighted nav item.
     *
     * @var string
     */
    protected $nav = '';

    /**
     * A list of properties to be used as parameters.
     *
     * @var array
     */
    protected $params = [];

    /**
     * Whether to display the right column.
     *
     * Passed to, and depends on template.
     *
     * @var boolean
     */
    protected $rightColumn = true;

    /**
     * Whether to allow the page to use the full page width (true) or
     * whether it should be contained within a div.column (false)
     *
     * Passed to, and depends on template.
     *
     * @var boolean
     */
    protected $fullWidth = false;

    /**
     * Which menu should be marked as 'active'.
     *
     * Passed to, and depends on template.
     *
     * @var string
     */
    protected $menuContext = '';

    protected $meta = [];

    /**
     * Run any global initialization functions.
     */
    public function __construct() {
        // Load messages and errors from the query string.
        Messenger::loadFromQuery();
        Messenger::loadFromSession();
        JS::add('/js/lightning.min.js');
        JS::startup('lightning.startup.init()');
        JS::startup('$(document).foundation()');
        CSS::add('/css/lightning.css');
        CSS::add('/css/font-awesome.min.css');
        CSS::add('/css/site.css');
        if (!empty($this->css)) {
            CSS::add($this->css);
        }
        if (!empty($this->js)) {
            JS::add($this->js);
        }

        $template = Template::getInstance();
    }

    public function get() {}

    /**
     * Prepare the output and tell the template to render.
     */
    public function output() {
        try {
            // Send globals to the template.
            $template = Template::getInstance();

            if (!empty($this->page)) {
                $template->set('content', $this->page);
            }

            $template->set('google_analytics_id', Configuration::get('google_analytics_id'));

            // TODO: Remove these, they should be called directly from the template.
            $template->set('errors', Messenger::getErrors());
            $template->set('messages', Messenger::getMessages());

            $template->set('site_name', Configuration::get('site.name'));
            $template->set('blog', Blog::getInstance());
            $template->set('full_width', $this->fullWidth);
            $template->set('right_column', $this->rightColumn);

            // Include the site title into the page title for meta data.
            $meta = $this->meta;
            if (empty($meta['title'])) {
                $meta['title'] = Configuration::get('meta_data.title');
            }
            elseif (!empty($meta['title']) && $site_title = Configuration::get('meta_data.title')) {
                $meta['title'] .= ' | ' . $site_title;
            }
            if (empty($meta['image'])) {
                $meta['image'] = Configuration::get('meta_data.image');
            }
            $template->set('meta', $meta);

            JS::set('menu_context', $this->menuContext);
            $template->render($this->template);
        } catch (Exception $e) {
            echo 'Error rendering template: ' . $e;
            exit;
        }
    }

    /**
     * Build a 404 page.
     */
    public function output404() {
        $this->page = 'page';
        if ($this->fullPage = PageModel::loadByUrl('404')) {
            http_response_code(404);
        } else {
            Output::http(404);
        }
    }

    /**
     * Determine if the current use has access to the page.
     */
    protected function hasAccess() {
        return false;
    }

    /**
     * Determine which handler in the page to run. This will automatically
     * determine if there is a form based on the submitted action variable.
     * If no action variable, it will call get() or post() or any other
     * rest method.
     */
    public function execute() {
        try {
            $request_type = strtolower(Request::type());

            if (!$this->hasAccess()) {
                Output::accessDenied();
            }

            // Outputs an error if this is a POST request without a valid token.
            $this->requireToken();

            // If there is a requested action.
            if ($action = Request::get('action')) {
                $method = Request::convertFunctionName($request_type, $action);
                if (method_exists($this, $method)) {
                    $this->{$method}();
                }
                else {
                    throw new Exception('There was an error processing your submission.');
                }
            } else {
                if (method_exists($this, $request_type)) {
                    $this->$request_type();
                } else {
                    // TODO: show 302
                    throw new Exception('Method not available');
                }
            }
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
        $this->output();
    }

    public function requireToken() {
        if (!$this->validateToken()) {
            Output::error(Language::translate('invalid_token'));
        }
    }

    /**
     * Make sure a valid token has been received.
     *
     * @return boolean
     *   Whether the token is valid.
     */
    public function validateToken() {
        // If this is a post request, there must be a valid token.
        if (!$this->ignoreToken && strtolower(Request::type()) == 'post') {
            $token = Request::post('token', 'base64');
            return !empty($token) && $token == Session::getInstance()->getToken();
        } else {
            // This is not a POST request so it's not required.
            return true;
        }
    }

    /**
     * Redirect the page to the same current page with the current query string.
     *
     * @param array
     *   Additional query string parameters to add to the current url.
     */
    public function redirect($params = array()) {
        $output_params = array();
        foreach ($this->params as $param) {
            if (isset($params[$param])) {
                $output_params[$param] = $params[$param];
            } elseif (isset($this->$param)) {
                $output_params[$param] = $this->$param;
            }
        }
        Navigation::redirect('/' . Request::getLocation(), $output_params);
    }

    public function setMeta($field, $value) {
        $this->meta[$field] = $value;
    }
}
