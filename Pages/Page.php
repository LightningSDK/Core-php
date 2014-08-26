<?php
/**
 * Contains the content HTML page controller.
 */

namespace Lightning\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Template;
use Lightning\Tools\ClientUser;
use Lightning\View\Field\BasicHTML;
use Lightning\View\Page as PageView;

class Page extends PageView {
    public function get() {
        $user = ClientUser::getInstance();
        $template = Template::getInstance();

        $content_locator = preg_replace('/\.html$/', '', $_GET['request']) ?: 'index';

        // Determine if the user can edit this page.
        $template->set('editable', $user->isAdmin());

        // Set the page template.
        $template->set('content', 'page');

        // LOAD PAGE DETAILS
        if($full_page = Database::getInstance()->selectRow('page', array('url' => array('LIKE', $content_locator)))){
            header('HTTP/1.0 200 OK');
            if($full_page['last_update'] > 0) {
                header("Last-Modified: ".gmdate("D, d M Y H:i:s", $full_page['last_update'])." GMT");
            }
        } elseif ($full_page = Database::getInstance()->selectRow('page', array('url' => '404'))) {
            header('HTTP/1.0 404 NOT FOUND');
            $full_page['url'] = $_GET['page'];
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

        // PREPARE FORM DATA CONTENTS
        foreach (array('title', 'keywords', 'description') as $meta_data) {
            $full_page[$meta_data] = htmlspecialchars($full_page[$meta_data], ENT_QUOTES);
            if(!empty($full_page[$meta_data])) {
                Configuration::set('page_' . $meta_data, str_replace("*", Configuration::get('page_' . $meta_data), $full_page[$meta_data]));
            }
        }

        if($full_page['url'] == "" && isset($_GET['page'])) {
            $full_page['url'] = $_GET['page'];
        }
        else {
            $full_page['url'] = htmlspecialchars($full_page['url'],ENT_QUOTES);
        }

        // FILL IN META INFO WITH DEFAULTS

        $full_page['body'] = htmlspecialchars_decode($full_page['body']);


        $template->set('page_header', $full_page['title']);
        $template->set('full_page', $full_page);
    }

    public function getNew() {
        // Prepare the template for a new page.
        $template = Template::getInstance();
        $template->set('action','new');

        // Prepare the form.
        $this->get();
    }

    public function postSave() {
        $user = ClientUser::getInstance();

        if($user->details['type'] < 5){
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
        $output['status'] = "OK";
        $output['url'] = $new_values['url'];
        $output['page_id'] = $page_id;
        Output::json($output);
    }

    public static function layoutOptions($default) {
        $options = array(
            0 => 'Right Column',
            1 => 'Full Width',
        );
        return BasicHTML::select('layout', $options, $default);
    }
}
