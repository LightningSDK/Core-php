<?php

namespace Lightning\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Messenger;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\Tools\ClientUser;
use Lightning\View\Page as PageView;

class Page extends PageView {
    public function get() {
        $user = ClientUser::getInstance();
        $template = Template::getInstance();

        $content_locator = preg_replace('/\.html$/', '', $_GET['request']) ?: 'index';

        // Determine if the user can edit this page.
        $editable = $user->details['type'] >= 5;
        $template->set('editable', $editable);

        // Set the page template.
        $template->set('content', 'page');

        if($editable){
            $action = Request::get('action');

            if ($action == 'new'){
                $template->set('action','new');
            } else if($action == 'save') {
                $page_id = Request::post('page_id', 'int');
                $url = str_ireplace(".html", "", $_POST['url']);
                $url = str_ireplace(".htm", "", $url);
                $url = str_replace(" ", "_", $url);

                $new_values = array(
                    'title' => Request::post('title'),
                    'url' => $url,
                    'keywords' => Request::post('keywords'),
                    'description' => Request::post('description'),
                    'site_map' => Request::post('sitemap', 'int'),
                    'body' => Request::post('page_body', 'html', '', '', true),
                    'last_update' => time(),
                );

                // Save the page.
                if ($page_id != 0) {
                    Database::getInstance()->update('pages', $new_values, array('page_id' => $page_id));
                } else {
                    $page_id = Database::getInstance()->insert('pages', $new_values);
                }

                if ($_POST['action'] == "save") {
                    $output = array();
                    $output['status'] = "OK";
                    $output['url'] = $url;
                    $output['page_id'] = $page_id;
                    header('Content-type: application/json');
                    echo json_encode($output);
                } else {
                    header("Location: /{$url}.html");
                }
                exit;
            }
        }


        // LOAD PAGE DETAILS
        if($full_page = Database::getInstance()->assoc1("SELECT * FROM pages WHERE url LIKE '{$content_locator}'")){
            header('HTTP/1.0 200 OK');
            if($full_page['last_update'] > 0) {
                header("Last-Modified: ".gmdate("D, d M Y H:i:s", $full_page['last_update'])." GMT");
            }
        } else {
            $full_page = Database::getInstance()->assoc1("SELECT * FROM pages WHERE url = '404'");
            header('HTTP/1.0 404 NOT FOUND');
            $full_page['url'] = $_GET['page'];
            $template->set('page_blank',true);
        }

        // PREPARE FORM DATA CONTENTS

        foreach (array('title', 'keywords', 'description') as $meta_data) {
            $full_page[$meta_data] = htmlspecialchars($full_page[$meta_data], ENT_QUOTES);
            if(!empty($full_page[$meta_data])) {
                Configuration::set('page_' . $meta_data, str_replace("*", Configuration::get('page_' . $meta_data), $full_page[$meta_data]));
            }
        }

        if($full_page['url'] == "" && isset($_GET['page']))
            $full_page['url'] = $_GET['page'];
        else
            $full_page['url'] = htmlspecialchars($full_page['url'],ENT_QUOTES);

        // FILL IN META INFO WITH DEFAULTS

        $full_page['body'] = htmlspecialchars_decode($full_page['body']);


        $template->set("full_page", $full_page);
    }

    public function post() {

    }
}
