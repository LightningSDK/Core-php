<?php
/**
 * Contains the content HTML page controller.
 */

namespace Lightning\Pages;

use Lightning\Tools\CKEditor;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Template;
use Lightning\Tools\ClientUser;
use Lightning\View\Field\BasicHTML;
use Lightning\View\JS;
use Lightning\View\Page as PageView;

class Page extends PageView {

    protected $new = false;

    protected function hasAccess() {
        return true;
    }

    public function get() {
        $user = ClientUser::getInstance();
        $template = Template::getInstance();

        $request = Request::getLocation();
        $content_locator = empty($request) ? 'index' : Request::getFromURL('/(.*)\.html$/') ?: '404';

        // Determine if the user can edit this page.
        $template->set('editable', $user->isAdmin());

        // Set the page template.
        $template->set('content', 'page');

        // LOAD PAGE DETAILS
        if ($full_page = $this->loadPage($content_locator)) {
            header('HTTP/1.0 200 OK');
            if (Configuration::get('page.modification_date') && $full_page['last_update'] > 0) {
                header("Last-Modified: ".gmdate("D, d M Y H:i:s", $full_page['last_update'])." GMT");
            }
        } elseif ($this->new) {
            $full_page['title'] = '';
            $full_page['keywords'] = '';
            $full_page['description'] = '';
            $full_page['url'] = '';
            $full_page['body'] = 'This is your new page.';
            $full_page['layout'] = 0;
            $full_page['site_map'] = 1;
            CKEditor::init();
            JS::startup('lightning.page.edit();');
        } elseif ($full_page = $this->loadPage('404')) {
            header('HTTP/1.0 404 NOT FOUND');
            $full_page['url'] = Request::get('page');
            $template->set('page_blank',true);
        } else {
            header('HTTP/1.0 404 NOT FOUND');
            $full_page['title'] = 'Lightning';
            $full_page['keywords'] = 'Lightning';
            $full_page['description'] = 'Lightning';
            $full_page['url'] = '';
            $full_page['body'] = 'Your site has not been set up.';
            $full_page['layout'] = 0;
            $full_page['site_map'] = 1;
            $template->set('page_blank',true);
        }

        // Replace special tags.
        if (!$user->isAdmin()) {
            $matches = array();

            preg_match_all('|{{.*}}|', $full_page['body'], $matches);

            foreach ($matches as $match) {
                if (!empty($match)) {
                    $match_clean = trim($match[0], '{} ');
                    $match_clean = explode('=', $match_clean);
                    switch ($match_clean[0]) {
                        case 'template':
                            $sub_template = new Template();
                            $full_page['body'] = str_replace(
                                $match[0],
                                $sub_template->render($match_clean[1], true),
                                $full_page['body']
                            );
                            break;
                    }
                }
            }
        }

        // PREPARE FORM DATA CONTENTS
        foreach (array('title', 'keywords', 'description') as $meta_data) {
            $full_page[$meta_data] = Scrub::toHTML($full_page[$meta_data]);
            if (!empty($full_page[$meta_data])) {
                Configuration::set('page_' . $meta_data, str_replace("*", Configuration::get('page_' . $meta_data), $full_page[$meta_data]));
            }
        }

        if ($full_page['url'] == "" && isset($_GET['page'])) {
            $full_page['url'] = $_GET['page'];
        }
        else {
            $full_page['url'] = Scrub::toHTML($full_page['url'],ENT_QUOTES);
        }

        $template->set('page_header', $full_page['title']);
        $template->set('full_page', $full_page);
        $template->set('full_width', $full_page['layout'] == 1);
    }

    public function loadPage($content_locator) {
        return Database::getInstance()->selectRow('page', array('url' => array('LIKE', $content_locator)));
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
            return $this->get();
        }

        $page_id = Request::post('page_id', 'int');
        $title = Request::post('title');
        $url = Request::post('url', 'url');

        // Create an array of the new values.
        $new_values = array(
            'title' => $title,
            'url' => !empty($url) ? $url : Scrub::url($title),
            'keywords' => Request::post('keywords'),
            'description' => Request::post('description'),
            'site_map' => Request::post('sitemap', 'int'),
            'body' => Request::post('page_body', 'html', '', '', true),
            'last_update' => time(),
            'layout' => Request::post('layout', 'int'),
        );

        // Save the page.
        if ($page_id != 0) {
            Database::getInstance()->update('page', $new_values, array('page_id' => $page_id));
        } else {
            $page_id = Database::getInstance()->insert('page', $new_values);
        }

        $output = array();
        $output['url'] = $new_values['url'];
        $output['page_id'] = $page_id;
        $output['title'] = $title;
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
