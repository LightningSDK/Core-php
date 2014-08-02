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
    public function output() {

// SEND GLOBALS TO SMARTY
        $template = Template::getInstance();
        foreach (array('title', 'keywords', 'description') as $meta_data) {
            $template->set('page_' . $meta_data, Configuration::get($meta_data));
        }
        $template->set('google_analytics_id', Configuration::get('google_analytics_id'));

        $template->set('user', ClientUser::getInstance());

        $template->set('recent_complaints',
            Database::getInstance()->assoc("SELECT complaint.complaint_id, post_date, officer_id, officer_last, officer_first, officer_badge, agency_id, agency_name, agency_city, agency_state, agency_short, body
						FROM complaint
						LEFT JOIN officer USING (officer_id)
						LEFT JOIN agency USING (agency_id)
						LEFT JOIN response ON (response.complaint_id = complaint.complaint_id AND response.response_id = complaint.initial_complaint+1)
						WHERE complaint.confirmed > 0  ORDER BY post_date DESC limit 20"));
        $template->set('aa', AA::getInstance());
        $template->set('errors', Messenger::getErrors());
        $template->set('messages', Messenger::getMessages());


        $template->set('site_name', Configuration::get('site.name'));
//$template->set('default_image', $default_image);
//$template->set('cms',$cms);
        $template->set('blog', Blog::getInstance());
        $template->render($template);
    }
}
