<?

namespace Overridable\Lightning\View;

use Lightning\Tools\Blog;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Source\AA;

class Page {
    /**
     * Run any global initialization functions.
     */
    public function __construct() {
        // Load messages and errors from the query string.
        Messenger::loadFromQuery();
    }

    /**
     * Prepare the output and tell the template to render.
     */
    public function output() {
        // Send globals to the template.
        $template = Template::getInstance();
        foreach (array('title', 'keywords', 'description') as $meta_data) {
            $template->set('page_' . $meta_data, Configuration::get('meta_data.' . $meta_data));
        }
        $template->set('google_analytics_id', Configuration::get('google_analytics_id'));

        // TODO: These should be called directly from the template.
        $template->set('errors', Messenger::getErrors());
        $template->set('messages', Messenger::getMessages());

        $template->set('site_name', Configuration::get('site.name'));
        $template->set('blog', Blog::getInstance());
        $template->render($template);
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

        if ($action) {
            if (in_array($request_type.$action, get_class_methods($this))) {
                $this->{$request_type.$action}();
                $this->output();
            }
            else {
                Messenger::error('There was an error processing your submission.');
            }
        } else {
            if (in_array($request_type, get_class_methods($this))) {
                $this->$request_type();
                $this->output();
            } else {
                // TODO: show 302
                echo 'Method not available';
                exit;
            }
        }
    }
}
