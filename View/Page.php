<?php

namespace Overridable\Lightning\View;

use Exception;
use Lightning\Model\Blog;
use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\CSS;
use Lightning\View\JS;

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
    protected $params = array();

    protected $rightColumn = true;
    protected $fullWidth = false;

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
        $template->set('full_width', $this->fullWidth);
        $template->set('right_column', $this->rightColumn);
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

            // TODO: These should be called directly from the template.
            $template->set('errors', Messenger::getErrors());
            $template->set('messages', Messenger::getMessages());

            $template->set('site_name', Configuration::get('site.name'));
            $template->set('blog', Blog::getInstance());
            JS::set('active_nav', $this->nav);
            $template->render($this->template);
        } catch (Exception $e) {
            echo 'Error rendering template: ' . $e;
            exit;
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
            Output::error('You submitted a form with an invalid token. Your requested has been ignored as a security precaution.');
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
}
