<?php

namespace lightningsdk\core\View;

use Exception;
use lightningsdk\core\Model\Blacklist;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Form as FormTool;
use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\Tools\Modules;
use lightningsdk\core\Tools\Navigation;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\Model\Tracker;

/**
 * The basic html page handler.
 *
 * @package lightningsdk\core\View
 */
class PageCore {

    /**
     * The template file.
     *
     * @var string|array
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
     * A template for the content within the page template.
     *
     * @var string|array
     */
    protected $page;

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

    protected $hideHeader = false;
    protected $hideMenu = false;
    protected $hideFooter = false;
    protected $share = true;
    protected $comment = null;

    /**
     * Which menu should be marked as 'active'.
     *
     * Passed to, and depends on template.
     *
     * @var string
     */
    protected $menuContext = '';

    /**
     * An array of meta data for the rendered page.
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Run any global initialization functions.
     */
    public function __construct() {
        // Load messages and errors from the query string.
        Messenger::loadFromQuery();
        Messenger::loadFromSession();
        Tracker::loadFromSession();
        JS::add('/js/lightning.min.js');
        JS::startup('lightning.startup.init()');
        foreach (Configuration::get('page.css.include', []) as $css) {
            CSS::add($css);
        }

        ClientUser::trackReferrer();
    }

    /**
     * Prepare the output and tell the template to render.
     *
     * @throws Exception
     */
    public function output() {
        if ($request_type = strtolower(Request::type()) == 'head') {
            return;
        }
        try {
            // Send globals to the template.
            $template = Template::getInstance();

            // Set the main content.
            if (!empty($this->page)) {
                $template->set('content', $this->page);
            }

            $this->setVars($template);

            $template->render($this->template);
        } catch (Exception $e) {
            echo 'Error rendering template: ' . $this->template . '<br>';
            throw $e;
        }
    }

    /**
     * Determine if the current use has access to the page.
     */
    protected function hasAccess() {
        return false;
    }

    protected function head() {
        // TODO: Add cache information
        return null;
    }

    /**
     * Determine which handler in the page to run. This will automatically
     * determine if there is a form based on the submitted action variable.
     * If no action variable, it will call get() or post() or any other
     * rest method.
     *
     * @throws Exception
     */
    public function execute() {
        try {
            $request_type = strtolower(Request::type());

            if (!$this->hasAccess()) {
                Output::accessDenied();
            }

            // If there is a requested action.
            if ($action = Request::get('action')) {
                $method = Request::convertFunctionName($action, $request_type);
            } else {
                $method = $request_type;
            }

            if (!method_exists($this, $method)) {
                Output::http(404);
            }

            // Outputs an error if this is a POST request without a valid token.
            if ($this->requiresToken()) {
                $this->requireToken();
            } else {
                // Create a token for when it's needed.
                FormTool::requiresToken();
            }

            // If this IP is blacklisted internally, block it completely.
            if ($request_type != 'get' && Blacklist::checkBlacklist(Request::getIP())) {
                throw new Exception('This action has been denied for security purposes.');
            }
            $this->{$method}();
        } catch (Exception $e) {
            Output::error($e->getMessage());
        }
        $this->output();
    }

    public function requiresToken() {
        return !$this->ignoreToken && strtolower(Request::type()) == 'post';
    }

    /**
     * @throws Exception
     */
    public function requireToken() {
        FormTool::validateToken();
    }

    /**
     * Redirect the page to the same current page with the current query string.
     *
     * @param array
     *   Additional query string parameters to add to the current url.
     */
    public function redirect($params = []) {
        Navigation::redirect('/' . Request::getLocation(), $params + $this->params);
    }

    public function setMeta($field, $value) {
        $this->meta[$field] = $value;
    }

    /**
     * Before rendering, default output values are set. This can be overridden by a custom page handler.
     *
     * @param Template template
     */
    protected function setVars($template) {

        // Lightning JS will handle these trackers.
        JS::set('google_analytics_id', Configuration::get('google_analytics_id'));
        JS::set('facebook_pixel_id', Configuration::get('facebook_pixel_id'));
        JS::set('google_adwords', Configuration::get('google_adwords', []));
        if (Configuration::get('debug')) {
            JS::set('debug', true);
        }

        // @deprecated
        $template->set('google_analytics_id', Configuration::get('google_analytics_id'));

        // TODO: Remove these, they should be called directly from the template.
        $template->set('errors', Messenger::getErrors());
        $template->set('messages', Messenger::getMessages());

        $template->set('site_name', Configuration::get('site.name'));
        $template->set('full_width', $this->fullWidth);
        $template->set('right_column', $this->rightColumn);
        $template->set('hide_header', $this->hideHeader);
        $template->set('hide_menu', $this->hideMenu);
        $template->set('hide_footer', $this->hideFooter);
        $template->set('share', $this->share);
        if ($this->comment === null) {
            $this->comment = $this->share;
        }
        $template->set('comment', $this->comment);

        // Include the site title into the page title for meta data.
        if (!empty($this->meta['title']) && $site_title = Configuration::get('meta_data.title')) {
            $this->meta['title'] .= ' | ' . $site_title;
        }

        // Load default metadata.
        $this->meta += Configuration::get('meta_data', []);
        if ($twitter = Configuration::get('social.twitter.url')) {
            $this->meta['twitter_site'] = $twitter;
            $this->meta['twitter_creator'] = $twitter;
        }
        $template->set('meta', $this->meta);

        JS::set('menu_context', $this->menuContext);
    }
}
