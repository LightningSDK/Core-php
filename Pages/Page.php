<?php
/**
 * Contains the content HTML page controller.
 */

namespace Lightning\Pages;

use DOMDocument;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Template;
use Lightning\Tools\ClientUser;
use Lightning\View\Field\BasicHTML;
use Lightning\View\HTML;
use Lightning\View\HTMLEditor\HTMLEditor;
use Lightning\View\JS;
use Lightning\View\Page as PageView;
use Lightning\Model\Page as PageModel;
use Lightning\View\Text;
use Lightning\View\Video\YouTube;

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
        $request = Request::getLocation();
        if (empty($request) || $request == 'index') {
            $content_locator = 'index';
            $this->menuContext = 'index';
        } else {
            $content_locator = Request::getFromURL('/(.*)\.html$/') ?: '404';
        }

        // LOAD PAGE DETAILS
        if ($this->fullPage = PageModel::loadByUrl($content_locator)) {
            header('HTTP/1.0 200 OK');
            $this->menuContext = $this->fullPage['menu_context'];
            if (Configuration::get('page.modification_date') && $this->fullPage['last_update'] > 0) {
                header("Last-Modified: ".gmdate("D, d M Y H:i:s", $this->fullPage['last_update'])." GMT");
            }
        } elseif ($this->new) {
            $this->fullPage['title'] = '';
            $this->fullPage['keywords'] = '';
            $this->fullPage['description'] = '';
            $this->fullPage['url'] = '';
            $this->fullPage['body'] = 'This is your new page.';
            $this->fullPage['layout'] = 0;
            $this->fullPage['site_map'] = 1;
            HTMLEditor::init();
            JS::startup('lightning.page.edit();');
        } elseif ($this->fullPage = PageModel::loadByUrl('404')) {
            http_response_code(404);
        } else {
            Output::http(404);
        }

        $this->prepare();
    }

    public function prepare() {
        $user = ClientUser::getInstance();
        $template = Template::getInstance();

        // Replace special tags.
        $this->fullPage['body_rendered'] = $this->renderContent($this->fullPage['body']);

        // Determine if the user can edit this page.
        if ($user->isAdmin()) {
            if (empty($this->fullPage['url']) || $this->fullPage['url'] == '404') {
                $this->fullPage['url'] = Request::getFromURL('/(.*)\.html$/') ?: 'index';
            }
            JS::set('page.source', $this->fullPage['body']);
            $template->set('editable', true);
        }

        // Set the page template.
        $template->set('content', 'page');

        // PREPARE FORM DATA CONTENTS
        foreach (array('title', 'keywords', 'description') as $field) {
            if (!empty($this->fullPage[$field])) {
                $this->setMeta($field, $this->fullPage[$field]);
            }
        }
        if ($image = HTML::getFirstImage($this->fullPage['body'])) {
            $this->setMeta('image', $image);
        }
        if (empty($this->fullPage['description'])) {
            $this->setMeta('description', Text::shorten($this->fullPage['body'], 500));
        }

        if ($this->fullPage['url'] == "" && isset($_GET['page'])) {
            $this->fullPage['url'] = $_GET['page'];
        }
        else {
            $this->fullPage['url'] = Scrub::toHTML($this->fullPage['url']);
        }

        $template->set('page_header', $this->fullPage['title']);
        $template->set('full_page', $this->fullPage);
        $template->set('full_width', $this->fullPage['layout'] == 1);
    }

    public function setPage($page) {
        $this->fullPage = $page;
    }

    protected function renderContent($content) {
        $matches = array();
        preg_match_all('|{{.*}}|', $content, $matches);
        foreach ($matches[0] as $match) {
            if (!empty($match)) {
                // Convert to HTML and parse it.
                $match_html = '<' . trim($match, '{} ') . '/>';
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($match_html);
                $element = $dom->getElementsByTagName('body')->item(0)->childNodes->item(0);
                $output = '';
                switch ($element->nodeName) {
                    case 'template':
                        $sub_template = new Template();
                        $output = $sub_template->render($element->getAttribute('name'), true);
                        break;
                    case 'youtube':
                        $output = YouTube::render($element->getAttribute('id'), [
                            'autoplay' => $element->getAttribute('autoplay') ? true : false,
                        ]);
                        if ($element->getAttribute('flex')) {
                            $output = '<div class="flex-video ' . ($element->getAttribute('widescreen') ? 'widescreen' : '') . '">' . $output . '</div>';
                        }
                }
                $content = str_replace(
                    $match,
                    $output,
                    $content
                );
            }
        }
        return $content;
    }

    public function getNew() {
        // Prepare the template for a new page.
        $template = Template::getInstance();
        $template->set('action','new');
        $this->new = true;

        // Prepare the form.
        $this->get();
    }

    public function postSave() {
        $user = ClientUser::getInstance();

        if (!$user->isAdmin()) {
            Output::accessDenied();
        }

        $page_id = Request::post('page_id', 'int');
        $title = Request::post('title');
        $url = Request::post('url', 'url');

        // Create an array of the new values.
        $new_values = array(
            'title' => $title,
            'url' => !empty($url) ? $url : Scrub::url($title),
            'menu_context' => Request::post('menu_context'),
            'keywords' => Request::post('keywords'),
            'description' => Request::post('description'),
            'site_map' => Request::post('sitemap', 'int'),
            'body' => Request::post('page_body', 'html', '', '', true),
            'last_update' => time(),
            'layout' => Request::post('layout', 'int'),
        );

        // Save the page.
        $update_values = $new_values;
        unset($update_values['url']);
        PageModel::insertOrUpdate($new_values, $update_values);

        $output = array();
        $output['url'] = $new_values['url'];
        $output['page_id'] = $page_id;
        $output['title'] = $title;
        $output['body_rendered'] = $this->renderContent($new_values['body']);
        Output::json($output);
    }

    /**
     * Create a dropdown selection of page layouts.
     *
     * @param integer $default
     *   The current selected layout.
     *
     * @return string
     *   The rendered HTML.
     */
    public static function layoutOptions($default) {
        $options = array(
            0 => 'Right Column',
            1 => 'Full Width',
        );
        return BasicHTML::select('page_layout', $options, intval($default));
    }
}
