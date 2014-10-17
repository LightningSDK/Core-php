<?

namespace Overridable\Lightning\View;

use Lightning\Model\Blog;
use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Request;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\CSS;
use Lightning\View\JS;

/**
 * The basic html page handler.
 *
 * @package Overridable\Lightning\View
 */
class Page {

    /**
     * The template file.
     *
     * @var string
     */
    protected $template = 'template';

    /**
     * A list of properties to be used as parameters.
     *
     * @var array
     */
    protected $params = array();

    /**
     * Run any global initialization functions.
     */
    public function __construct() {
        // Load messages and errors from the query string.
        Messenger::loadFromQuery();
        JS::add('/js/fastclick.min.js');
        JS::add('/js/jquery.min.js');
        JS::add('/js/jquery.cookie.min.js');
        JS::add('/js/modernizr.min.js');
        JS::add('/js/placeholder.min.js');
        JS::add('/js/foundation.min.js');
        JS::add('/js/lightning.min.js');
        JS::add('/js/jquery.validate.min.js');
        JS::startup('$(document).foundation();');
        CSS::add('/css/foundation.css');
        CSS::add('/css/normalize.css');
        CSS::add('/css/site.css');
    }

    /**
     * Prepare the output and tell the template to render.
     */
    public function output() {
        // Send globals to the template.
        $template = Template::getInstance();

        if (!empty($this->page)) {
            $template->set('content', $this->page);
        }

        foreach (array('title', 'keywords', 'description') as $meta_data) {
            $template->set('page_' . $meta_data, Configuration::get('meta_data.' . $meta_data));
        }
        $template->set('google_analytics_id', Configuration::get('google_analytics_id'));

        // TODO: These should be called directly from the template.
        $template->set('errors', Messenger::getErrors());
        $template->set('messages', Messenger::getMessages());

        $template->set('site_name', Configuration::get('site.name'));
        $template->set('blog', new Blog());
        $template->render($this->template);
    }

    /**
     * Determine which handler in the page to run. This will automatically
     * determine if there is a form based on the submitted action variable.
     * If no action variable, it will call get() or post() or any other
     * rest method.
     */
    public function execute() {
        $action = ucfirst(Request::get('action'));
        $request_type = strtolower(Request::type());

        // If this is a post request, there must be a valid token.
        if ($request_type == 'post') {
            $token = Request::post('token', 'hex');
            if (empty($token) || $token != Session::getInstance()->getToken()) {
                Navigation::redirect('/message?err=invalid_token');
            }
        }

        // If there is a requested action.
        if ($action) {
            $method = Request::convertFunctionName($request_type, $action);
            if (method_exists($this, $method)) {
                $this->{$method}();
                $this->output();
            }
            else {
                Messenger::error('There was an error processing your submission.');
            }
        } else {
            if (method_exists($this, $request_type)) {
                $this->$request_type();
                $this->output();
            } else {
                // TODO: show 302
                echo 'Method not available';
                exit;
            }
        }
    }

    /**
     * Redirect the page to the same current page with the current query string.
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
        Navigation::redirect('/' . Request::get('request'), $output_params);
    }
}
