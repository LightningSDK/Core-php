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
        $path = explode('/', Request::get('request'));

        $blog = BlogModel::getInstance();

        if (preg_match('/.*\.htm/', $path[0])) {
            $blog->loadByURL($path[0]);
        }
        elseif ($blog_id) {
            $blog->loadById($blog_id);
        }
        elseif (array_shift($path) == 'blog') {
            if (!empty($path)) {
                $page = is_numeric($path[count($path) - 1]) ? $path[count($path) - 1] : 1;
                if ($path[0] == 'category') {
                    // Load category roll
                    $blog->loadList($page, 'category', $path[1]);
                } elseif ($path[0] == 'author') {
                    // Load an author roll.
                    $blog->loadList($page, 'author', $path[1]);
                } elseif (!empty($page)) {
                    $blog->loadList($page);
                } else {
                    // Try to load a specific blog.
                    $blog->loadByURL($path[0]);
                }
            }
        }

        if (empty($blog->posts)) {
            // Fall back, load blogroll
            $blog->loadList(1);
        }
        $template = Template::getInstance();
        if (count($blog->posts) == 1) {
            $template->set('page_section','blog');
        } else {
            // If there is more than one, we show a list with short bodies.
            $blog->shorten_body = true;
        }

        if (count($blog->posts) == 1) {
            foreach (array('title', 'keywords', 'description', 'author') as $meta_data) {
                switch ($meta_data) {
                    case 'title' :
                        $value = Configuration::get('page_' . $meta_data).' | '.Scrub::toHTML($blog->body($blog->posts[0]['author_name'],true));
                        break;
                    case 'description':
                        $value = Scrub::toHTML($blog->body($blog->posts[0]['body'],true));
                        break;
                    case 'author' :
                        $value = Scrub::toHTML($blog->body($blog->posts[0]['author_name'],true));
                        break;
                    default:
                        $value = Scrub::toHTML($blog->body($blog->posts[0][$meta_data],true));
                }
                $template->set('page_'.$meta_data, $value);
            }
        }

        //meta facebook image
        if (count($blog->posts) == 1 && !empty($blog->posts[0]['header_image'])) {
            $template->set('og_image', $blog->posts[0]['header_image']);
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
                if ($_POST['check_val'] == md5($_POST['email'].$_POST['name'].$_POST['comment'])) {
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
                if ($user->isAdmin() && $_POST['blog_comment_id'] > 0) {
                    Database::getInstance()->delete('blog_comment', array('blog_comment_id' => Request::post('blog_comment_id', 'int')));
                    echo "ok";
                } else {
                    echo "access denied";
                }
                exit;

            case 'approve_blog_comment':
                $user = ClientUser::getInstance();
                if ($user->isAdmin() && $_POST['blog_comment_id'] > 0) {
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
