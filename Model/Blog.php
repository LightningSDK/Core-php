<?php

namespace lightningsdk\core\Model;

use lightningsdk\core\View\Pagination;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Database;
use lightningsdk\core\Tools\Singleton;

class BlogOverridable extends Singleton {

    protected $post_count = 0;
    protected $isList = false;

    public $id = 0;
    public $posts = [];
    public $shorten_body = false;
    public $y = 0;
    public $m = 0;
    public $category='';
    public $category_url = '';
    protected $categories;
    public $list_per_page = 10;
    public $page = 1;

    /**
     * Overrides parent function.
     *
     * @param boolean $create
     *   Whether to create a new instance.
     *
     * @return Blog
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
    }

    public function getAuthorID($search_value) {
        return Database::getInstance()->selectField(
            'user_id',
            BlogPost::TABLE . BlogPost::AUTHOR_TABLE,
            ['author_url' => ['LIKE', $search_value]]
        );
    }

    public function isList() {
        return $this->isList;
    }

    public function loadContentByURL($url) {
        $this->isList = false;
        $this->posts = BlogPost::loadPosts(['url' => $url]);
        if (!empty($this->posts)) {
            $this->id = $this->posts[0]['blog_id'];
        }
    }

    public function loadContentById($id) {
        $this->isList = false;
        $this->posts = BlogPost::loadPosts(['blog_id' => $id]);
        if (!empty($this->posts)) {
            $this->id = $this->posts[0]['blog_id'];
        }
    }

    public function loadList($search_field = null, $search_value = null) {
        $this->isList = true;
        $join = [];
        $where = [];
        if ($this->y != 0) {
            if ($this->m > 0) {
                // SELECT A MONTH
                $where['time'] = ['BETWEEN', mktime(0,0,0,$this->m,1,$this->y), mktime(0,0,0,$this->m+1,1,$this->y)];
            } else {
                $where['time'] = ['BETWEEN', mktime(0,0,0,1,1,$this->y), mktime(0,0,0,1,1,$this->y+1)];
            }
        }

        elseif ($search_field == 'category') {
            $join[] = [
                'JOIN',
                ['cat_search' => BlogPost::TABLE . '_blog_category'],
                'ON cat_search.blog_id = ' . BlogPost::TABLE . '.blog_id'
            ];
            $where['cat_search.cat_id'] = $search_value;
        }

        elseif ($search_field == 'author') {
            $where[BlogPost::TABLE . '.user_id'] = $search_value;
        }

        $limit = '';
        if ($this->list_per_page > 0) {
            $limit = " LIMIT " . intval(($this->page -1) * $this->list_per_page) . ", {$this->list_per_page}";
        }

        $this->posts = BlogPost::loadPosts($where, $join, $limit);

        $this->loadPostCount($where, $join);
    }

    protected function loadPostCount($where, $join) {
        $this->post_count = Database::getInstance()->count([
                'from' => BlogPost::TABLE,
                'join' => $join,
            ],
            $where
        );
    }

    protected function loadCategories($force = false) {
        if ($force || empty($this->categories)) {
            $this->categories = Database::getInstance()->selectColumnQuery([
                'select' => ['category', 'cat_url'],
                'from' => 'blog_category',
            ]);
        }
    }

    public function pagination() {
        // do noting if we don't have more than one page
        if (!$this->isList() || $this->post_count <= $this->list_per_page) {
            return false;
        }

        // set up some variables
        $pages = ceil($this->post_count / $this->list_per_page);

        if ($this->m > 0) {
            $base_link = "/blog/archive/{$this->y}/{$this->m}-%%";
        } else if ($this->y > 0) {
            $base_link = "/blog/archive/{$this->y}-%%";
        } else if (!empty($this->category)) {
            $base_link = '/blog/category/' . $this->category_url . '-%%';
        } else {
            $base_link = '/blog/page/%%';
        }

        $pagination = new Pagination([
            'page' => $this->page,
            'pages' => $pages,
            'base_path_replace' => $base_link,
        ]);

        return $pagination->render();
    }

    public function renderRecentList($remote=false) {
        $list = BlogPost::getRecent();
        $target = $remote ? "target='_blank'" : '';
        if (!empty($list)) {
            echo "<ul>";
            foreach($list as $r) {
                echo "<li><a href='/blog/{$r['url']}' {$target}>{$r['title']}</a></li>";
            }
            echo "</ul>";
        }
    }

    public function renderCategoriesList() {
        $list = BlogPost::getAllCategories();
        if (!empty($list)) {
            echo "<ul>";
            foreach($list as $r)
                echo "<li><a href='/blog/category/". $r['cat_url'] . "'>{$r['category']}</a> ({$r['count']})</li>";
            echo "</ul>";
        }
    }

    /**
     * Load a blog by it's URL.
     *
     * @param string $url
     *   The blog's url.
     *
     * @return int
     *   The blog ID.
     */
    public function loadBlogURL($url) {
        $this->isList = false;
        $url = preg_replace('/.htm$/', '', $url);
        $this->posts = BlogPost::loadPosts(['url' => $url]);
        if ($this->posts) {
            $this->id = $this->posts[0]['blog_id'];
        } else {
            $this->id = 0;
        }
        return $this->id;
    }

    /**
     * Load a blog by it's ID.
     *
     * @param int $id
     *   The blog ID.
     *
     * @return int
     *   The blog ID.
     */
    public function loadBlogID($id) {
        $this->isList = false;
        $this->posts = BlogPost::loadPosts([BlogPost::TABLE.'.blog_id' => $id]);
        if ($this->posts) {
            $this->id = $this->posts[0]->id;
        } else {
            $this->id = 0;
        }
    }

    public static function getSitemapUrls() {
        $web_root = Configuration::get('web_root');
        $blogs = Database::getInstance()->select([
            'from' => BlogPost::TABLE,
        ],
            [],
            [
                [BlogPost::TABLE => ['blog_time' => 'time']],
                'url',
            ],
            'GROUP BY blog_id'
        );

        $urls = [];
        foreach($blogs as $b) {
            $urls[] = [
                'loc' => $web_root . "/blog/{$b['url']}",
                'lastmod' => date("Y-m-d", $b['blog_time'] ?: time()),
                'changefreq' => 'yearly',
                'priority' => .3,
            ];
        }
        return $urls;
    }
}
