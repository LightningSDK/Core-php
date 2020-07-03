<?php
/**
 * Contains the content HTML page controller.
 */

namespace lightningsdk\core\Pages;

use DOMDocument;
use lightningsdk\core\Model\URL;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Modules;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\Tools\Scrub;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\View\CSS;
use lightningsdk\core\View\HTML;
use lightningsdk\core\View\HTMLEditor\Markup;
use lightningsdk\core\View\JS;
use lightningsdk\core\View\Page as PageView;
use lightningsdk\core\Model\Page as PageModel;
use lightningsdk\core\View\Text;

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
        // Load any page initters from modules
        if (!empty($this->fullPage['modules'])) {
            $modules = json_decode($this->fullPage['modules'], true);
            foreach ($modules as $module) {
                if ($init_method = Configuration::get('modules.' . $module . '.init_view')) {
                    // todo: this is deprecated, and is only used in checkout-stripe module, but need to
                    // verify that the sitemanager-checkout-stripe module will override it
                    call_user_func($init_method);
                } else {
                    Modules::initPage($module);
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
        $template->set('content', ['page', 'lightningsdk/core']);

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

        CSS::inline($this->fullPage['css']);
        JS::inline($this->fullPage['js']);

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
