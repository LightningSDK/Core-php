<?

namespace Overridable\Lightning\View;

use Lightning\Tools\Blog;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;
use Lightning\Tools\Template;
use Source\AA;

class Page {
    public function __construct() {
        Messenger::loadFromQuery();
    }

    public function output() {

        // SEND GLOBALS TO SMARTY
        $template = Template::getInstance();
        foreach (array('title', 'keywords', 'description') as $meta_data) {
            $template->set('page_' . $meta_data, Configuration::get('meta_data.' . $meta_data));
        }
        $template->set('google_analytics_id', Configuration::get('google_analytics_id'));

        $template->set('errors', Messenger::getErrors());
        $template->set('messages', Messenger::getMessages());

        $template->set('site_name', Configuration::get('site.name'));
        $template->set('blog', Blog::getInstance());
        $template->render($template);
    }
}
