<?php
/**
 * @file
 * Contains Lightning\Pages\Blog
 */

namespace Lightning\Pages;

use Lightning\Model\Blog as BlogModel;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Table;
use Lightning\Tools\Template;
use Lightning\View\Page;

/**
 * A page handler for viewing and editing the blog.
 *
 * @package Lightning\Pages
 */
class Blog extends Page {

    protected $nav = 'blog';

    protected function hasAccess() {
        return true;
    }

    public function get() {
        $blog_id = Request::get('id', 'int') | Request::get('blog_id', 'int');
        $blog_url = Request::get('request');

        // SEE IF A SPECIFIC BLOG ARTICLE IS BEING REQUESTED.
        $blog = BlogModel::getInstance();
        if($blog_id > 0) {
            $blog->fetch_blog_id($blog_id);
        }
        elseif (!empty($blog_url)) {
            $blogroll = explode("/",$blog_url);
            if($blogroll[0] == "blog" && !empty($blogroll[1]) && $blogroll[1] == 'page'){
                $blog->page = intval($blogroll[2]);
            } else {
                $blog->fetch_blog_url(preg_replace('/\.htm$/', '', $blog_url));
            }
        }

        // SEE IF A SPECIFIC CATEGORY IS BEING REQUESTED.
        if (isset($_GET['blog_cat'])) {
            $blog->category = $_GET['blog_cat'];
        }

        // SEE IF THE ARCHIVE IS BEING REQUESTED.
        if (isset($_GET['archive'])) {
            $archive = str_replace('.htm','',$_GET['archive']);
            $archive = explode("-",$archive);
            if(count($archive) == 2)
                $blog->page = intval($archive[1]);
            $archive = explode("/",$archive[0]);
            $blog->y = intval($archive[0]);
            $blog->m = intval($archive[1]);
        }

        $template = Template::getInstance();

        if ($blog->id > 0) {
            // IF THERE IS A SPECIFIC BLOG, SHOW IT
            $template->set('page_section','blog');
        } else {
            // SHOW THE BLOGROLL OR ARCHIVE
            $blog->list_post();
            if(count($blog->posts) > 1)
                $blog->shorten_body = true;
            $template->set('page_section', 'blog_list');
        }
        if(count($blog->posts) == 1){
            foreach (array('title', 'keywords', 'description') as $meta_data) {
                $value = $meta_data == 'description' ?
                    Scrub::toHTML($blog->body($blog->posts[0]['body'],true)) :
                    Scrub::toHTML($blog->body($blog->posts[0][$meta_data],true));
                Configuration::set('page_' . $meta_data, str_replace("*", $value, Configuration::get('page_' . $meta_data)));
            }
        }

        foreach($blog->posts as $key => $post) {
            //Header images
            $header_image = '';

            if(empty($post['header_image']) OR $post['header_image'] == '') {
                preg_match_all('/<img\s+.*?src=[\"\']?([^\"\' >]*)[\"\']?[^>]*>/i',$post['body'],$matches,PREG_SET_ORDER);
                if(!empty($matches[0][1])) {
                    $header_image = (file_exists(HOME_PATH.$matches[0][1]))?$matches[0][1]:NULL;
                } else {
                    $header_image = NULL;
                }
            } else {
                $header_image = (file_exists(HOME_PATH.'/img/blog/'.$post[header_image]))?'/img/blog/'.$post[header_image]:NULL;
            }

            $blog->posts[$key]['default_header_image'] = $header_image;
        }

        $template->set('content', 'blog');
    }

    public function post() {
        $blog_id = Request::get('id', 'int') | Request::get('blog_id', 'int');
        $action = Request::get('action');

        // AUTHORIZE A BLOG COMMENT.
        switch ($action) {
            case 'post_comment_check':
                echo md5($_POST['email'].$_POST['name'].$_POST['comment']);
                exit;

            case 'post_comment':
                // FIRST CHECK FOR SPAM
                if($_POST['check_val'] == md5($_POST['email'].$_POST['name'].$_POST['comment'])){
                    $values = array(
                        'blog_id' => $blog_id,
                        'ip_address' => Request::server('ip_int'),
                        'email_address' => Request::post('email', 'email'),
                        'name' => Request::post('name'),
                        'comment' => Request::post('comment'),
                        'time' => time(),
                    );
                    Database::getInstance()->insert('blog_comment', $values);
                    echo "success";
                }
                else {
                    echo "spam error";
                }
                exit;

            case 'remove_blog_comment':
                $user = ClientUser::getInstance();
                if ($user->details['type'] >= 5 && $_POST['blog_comment_id'] > 0) {
                    Database::getInstance()->delete('blog_comment', array('blog_comment_id' => Request::post('blog_comment_id', 'int')));
                    echo "ok";
                } else {
                    echo "access denied";
                }
                exit;

            case 'approve_blog_comment':
                $user = ClientUser::getInstance();
                if ($user->details['type'] >= 5 && $_POST['blog_comment_id'] > 0) {
                    Database::getInstance()->update(
                        'blog_comment',
                        array('approved' => 1),
                        array('blog_comment_id' => Request::post('blog_comment_id', 'int'))
                    );
                    echo "ok";
                    exit;
                }
        }
    }

    public function post_to_facebook($blog_data) {
        require_once HOME_PATH . '/include/facebook/facebook.php';

        $facebook = new Facebook(array(
            'appId'  => FACEBOOK_APP_ID,
            'secret' => FACEBOOK_APP_SECRET,
            'cookie' => true,
            'scope' => 'manage_pages',
        ));

        $user_id = $facebook->getUser();
        print_r($user_id);
        $access_token = $facebook->getAccessToken();
        print_r($access_token);

        $attachment = array(
            'access_token' => $access_token,
            'message' => 'this is my message',
            'name' => 'name',
            'link' => ROOT_URL . $blog_data['url'] . '.htm',
            'caption' => $blog_data['title'],
            'description' => $blog_data['title'],
        );
        if ($image = get_first_image($blog_data['body'])) {
            $attachment['picture'] = $image;
        }
        $facebook->api(FACEBOOK_BLOG_PAGE . '/feed', 'post', $attachment);
    }

    public function get_first_image($source) {
        preg_match('/<img(.*)>/i', $source, $results);
        preg_match('/src[= "\']+(.*)["\']/i', $results[1], $results);
        $url = $results[1];
        if (preg_match('%^/%', $url)) {
            $url = ROOT_URL . $url;
        }
        return $url;
    }
}
