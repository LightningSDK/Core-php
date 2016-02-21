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
    protected $page = 'blog';

    protected function hasAccess() {
        return true;
    }

    public function get() {
        $blog_id = Request::get('id', 'int') | Request::get('blog_id', 'int');
        $path = explode('/', Request::getLocation());

        $blog = BlogModel::getInstance();

        if (preg_match('/.*\.htm/', $path[0])) {
            // TODO: This might not be necessary since it is searched below.
            $blog->loadContentByURL($path[0]);
        }
        elseif ($blog_id) {
            $blog->loadContentById($blog_id);
        }
        else {
            if (!empty($path[0]) || count($path) > 2) {
                // This page num can be in index 2 (blog/page/#) or index 3 (blog/category/a-z/#).
                $blog->page = is_numeric($path[count($path) - 1]) ? $path[count($path) - 1] : 1;
                if (empty($path[1])) {
                    $blog->loadList(1);
                } elseif ($path[1] == 'category' && !empty($path[1])) {
                    // Load category roll
                    $category = preg_replace('/\.htm$/', '', $path[2]);
                    $c_parts = explode('-', $category);
                    if (is_numeric(end($c_parts))) {
                        $blog->page = array_pop($c_parts);
                    }
                    $blog->category = implode('-', $c_parts);
                    $blog->loadList('category', $blog->category);
                } elseif ($path[1] == 'author' && !empty($path[1])) {
                    // Load an author roll.
                    $blog->loadList('author', preg_replace('/\.htm$/', '', $path[2]));
                } elseif (!empty($blog->page)) {
                    $blog->loadList();
                } else {
                    // Try to load a specific blog.
                    $blog->loadContentByURL($path[0]);
                }
            }
            else {
                // Fall back, load blogroll
                // TODO: This should only happen on /blog, otherwise it should return a 404
                $blog->loadList();
            }
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
                        $titles = [];
                        if (!empty($blog->posts[0]['title'])) {
                            $titles[] = $blog->posts[0]['title'];
                        }
                        if ($title = Configuration::get('meta_data.title')) {
                            $titles[] = $title;
                        }
                        if ($title = Scrub::toHTML($blog->body($blog->posts[0]['author_name'], true))) {
                            $titles[] = $title;
                        }
                        $value = implode(' | ', $titles);
                        break;
                    case 'description':
                        $value = Scrub::toHTML($blog->body($blog->posts[0]['body'], true));
                        break;
                    case 'author' :
                        $value = Scrub::toHTML($blog->body($blog->posts[0]['author_name'], true));
                        break;
                    default:
                        $value = Scrub::toHTML($blog->body($blog->posts[0][$meta_data], true));
                }
                $template->set('page_' . $meta_data, $value);
            }
        }

        //meta facebook image
        if (count($blog->posts) == 1 && !empty($blog->posts[0]['header_image'])) {
            $template->set('og_image', Configuration::get('web_root') . $blog->getImage($blog->posts[0]));
        } elseif ($default_image = Configuration::get('blog.default_image')) {
            $template->set('og_image', Configuration::get('web_root') . $default_image);
        }
    }
}
