<?php
/**
 * Contains the content HTML page controller.
 */

namespace Lightning\Pages;

use DOMDocument;
use Lightning\Model\URL;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Template;
use Lightning\Tools\ClientUser;
use Lightning\View\HTML;
use Lightning\View\HTMLEditor\Markup;
use Lightning\View\JS;
use Lightning\View\Page as PageView;
use Lightning\Model\Page as PageModel;
use Lightning\View\Text;

class Page extends PageView {

    /**
     * Whether this page was just created and should be inserted as a new page.
     *
     * @var boolean
     */
    protected $new = false;

    /**
     * Page content from the database.
     *
     * @var array
     */
    protected $fullPage;

    protected function hasAccess() {
        return true;
    }

    public function get() {
        $slug = Request::getLocation();
        if (empty($slug) || $slug == 'index') {
            // If there is no slug, this is the home page
            $content_locator = 'index';
            $this->menuContext = 'index';
        } else {
            // Backwards compatibility with *.html urls.
            $content_locator = Request::getFromURL('/(.*)\.html$/') ?: $slug;
        }

        // LOAD PAGE DETAILS
        if ($this->fullPage = PageModel::loadByURL($content_locator)) {
            if (preg_match('/^[0-9]{3}$/', $content_locator)) {
                // If the page is a 3 digit code, this is treated as a custom error page.
                http_response_code($content_locator);
            } else {
                // Otherwise it's a 200 page.
                http_response_code(200);
            }
            $this->menuContext = $this->fullPage['menu_context'];
        } elseif ($this->fullPage = PageModel::loadByURL('404')) {
            $this->fullPage['page_id'] = false;
            $this->fullPage['url'] = $content_locator;
            http_response_code(404);
        } else {
            // This should still be editable because we know it's within the .html handler.
            Output::http(404, true);
        }

        $this->prepare();
    }

    public function prepare($admin_editable = true) {
        $user = ClientUser::getInstance();
        $template = Template::getInstance();

        // Init modules
        if (!empty($this->fullPage['modules'])) {
            $modules = json_decode($this->fullPage['modules'], true);
            foreach ($modules as $module) {
                if ($init_method = Configuration::get('modules.' . $module . '.init_view')) {
                    call_user_func($init_method);
                }
            }
        }

        // Replace special tags.
        $templateVars = [];
        $this->fullPage['body_rendered'] = Markup::render($this->fullPage['body'], $templateVars);
        foreach ($templateVars as $key => $value) {
            $template->set($key, $value);
        }

        // Determine if the user can edit this page.
        if ($user->isAdmin()) {
            $this->fullPage['new_title'] = ucwords(preg_replace('/[\-_]/', ' ', $this->fullPage['url']));
            JS::set('page.source', $this->fullPage['body']);
            $template->set('editable', $admin_editable);
        }

        // Set the page template.
        $template->set('content', ['page', 'Lightning']);

        // PREPARE FORM DATA CONTENTS
        foreach (['title', 'keywords'] as $field) {
            if (!empty($this->fullPage[$field])) {
                $this->setMeta($field, html_entity_decode($this->fullPage[$field]));
            }
        }

        // Set the preview image.
        if (!empty($this->fullPage['preview_image'])) {
            if ($this->fullPage['preview_image'] != 'default') {
                $this->setMeta('image', URL::getAbsolute($this->fullPage['preview_image']));
            }
        } elseif ($image = HTML::getFirstImage($this->fullPage['body'])) {
            $this->setMeta('image', URL::getAbsolute($image));
        }

        if (empty($this->fullPage['description'])) {
            $this->setMeta('description', Text::shorten(html_entity_decode($this->fullPage['body'], 500)));
        }

        // If there is no page here, we need to set the URL for editing.
        if ($this->fullPage['url'] == '' && isset($_GET['page'])) {
            $this->fullPage['url'] = $_GET['page'];
        } else {
            $this->fullPage['url'] = Scrub::toHTML($this->fullPage['url']);
        }

        $template->set('page_header', $this->fullPage['title']);
        $this->fullWidth = $this->fullPage['full_width'] == 1;
        $this->rightColumn = $this->fullPage['right_column'] == 1;
        $this->hideHeader = !empty($this->fullPage['hide_header']);
        $this->hideMenu = !empty($this->fullPage['hide_menu']);
        $this->hideFooter = !empty($this->fullPage['hide_footer']);
        $this->share = !empty($this->fullPage['hide_share']);
        // Pass the page object.
        $template->set('full_page', $this->fullPage);

        if (!empty($this->fullPage['template'])) {
            $template->setTemplate($this->fullPage['template']);
        }
    }

    public function setPage($page) {
        $this->fullPage = $page;
    }

    public function getNew() {
        // Prepare the template for a new page.
        $template = Template::getInstance();
        $template->set('action', 'new');
        $this->new = true;

        // Prepare the form.
        $this->get();
    }
}
