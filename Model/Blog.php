<?php

namespace Overridable\Lightning\Model;

use Lightning\View\Pagination;
use Lightning\View\Text;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Scrub;
use Lightning\Tools\Singleton;

class Blog extends Singleton {

    const BLOG_TABLE = 'blog';
    const CATEGORY_TABLE = 'blog_category';
    const BLOG_CATEGORY_TABLE = 'blog_blog_category';
    const AUTHOR_TABLE = 'blog_author';
    const IMAGE_PATH = 'img/blog';

    protected $post_count = 0;
    protected $isList = false;

    public $id = 0;
    public $posts = array();
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

    public function body($body, $force_short = false) {
        if ($this->shorten_body || $force_short) {
            return $this->shortBody($body);
        } else {
            return $body;
        }
    }

    public function shortBody($body, $length = 250) {
        return Text::shorten($body, $length);
    }

    protected function getCategory($search_value) {
        return Database::getInstance()->selectRow(
            static::CATEGORY_TABLE,
            ['cat_url' => ['LIKE', $search_value]]
        );
    }

    protected function getAuthorID($search_value) {
        return Database::getInstance()->selectField(
            'user_id',
            static::AUTHOR_TABLE,
            ['author_url' => ['LIKE', $search_value]]
        );
    }

    public function isList() {
        return $this->isList;
    }

    public function loadList($search_field = null, $search_value = null) {
        $this->isList = true;
        $join = [];
        $where = [];
        if ($this->y != 0) {
            if ($this->m > 0) // SELECT A MONTH
                $where['time'] = array('BETWEEN', mktime(0,0,0,$this->m,1,$this->y), mktime(0,0,0,$this->m+1,1,$this->y));
            else
                $where['time'] = array('BETWEEN', mktime(0,0,0,1,1,$this->y), mktime(0,0,0,1,1,$this->y+1));
        }

        elseif ($search_field == 'category') {
            if ($cat = $this->getCategory($search_value)) {
                $this->category = $search_value;
                $this->category_url = $cat['cat_url'];
                $join[] = [
                    'JOIN',
                    array('cat_search' => static::BLOG_TABLE . '_blog_category'),
                    'ON cat_search.blog_id = ' . static::BLOG_TABLE . '.blog_id'
                ];
                $where['cat_search.cat_id'] = $cat['cat_id'];
            }
        }

        elseif ($search_field == 'author') {
            if ($author_id = $this->getAuthorID($search_value)) {
                $where[static::BLOG_TABLE . '.user_id'] = $author_id;
            }
        }

        $limit = '';
        if ($this->list_per_page > 0) {
            $limit = " LIMIT " . intval(($this->page -1) * $this->list_per_page) . ", {$this->list_per_page}";
        }

        $this->loadPosts($where, $join, $limit);
        $this->postProcessResults();

        $this->loadPostCount($where, $join);
    }

    protected function loadPostCount($where, $join) {
        $this->post_count = Database::getInstance()->count(
            [
                'from' => static::BLOG_TABLE,
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

    public function getCatURL($category) {
        $this->loadCategories();
        return !empty($this->categories[$category]) ? $this->categories[$category] : null;
    }

    protected function loadPosts($where = [], $join = [], $limit = '') {
        $this->posts = Database::getInstance()->selectAll(
            array(
                'from' => static::BLOG_TABLE,
                'join' => array_merge($join, $this->joinAuthorCatTables()),
            ),
            $where,
            $this->blogFields(),
            'GROUP BY ' . static::BLOG_TABLE . '.blog_id ORDER BY time DESC ' . $limit
        );
    }

    public function loadContentByURL($url) {
        $this->isList = false;
        $url = preg_replace('/.htm$/', '', $url);
        $this->loadPosts(['url' => $url]);
        $this->postProcessResults();
    }

    public function loadContentByID($id) {
        $this->isList = false;
        $this->loadPosts([static::BLOG_TABLE.'.blog_id' => $id]);
        $this->postProcessResults();
    }

    protected function postProcessResults() {
        foreach ($this->posts as &$post) {
            $post['categories'] = empty($post['categories']) ? array() : explode(',', $post['categories']);
        }

        //Header images
        foreach($this->posts as &$post) {
            $post['header_image'] = $this->getImage($post);
        }
    }

    public function getImage(&$post) {
        $header_image = NULL;
        if (!empty($post['header_image'])) {
            // Image from upload.
            return '/' . Blog::IMAGE_PATH . '/' . $post['header_image'] . '.jpg';
        }
        elseif (empty($post['header_image']) && $img = $this->getFirstImage($post['body'])) {
            // Image from post.
            $post['header_from_source'] = true;
            return $img;
        }
        else {
            // Default image.
            return Configuration::get('blog.default_image');
        }
    }

    protected function joinAuthorCatTables() {
        return array(
            // Join categories
            array('LEFT JOIN',
                static::BLOG_TABLE . '_blog_category',
                'ON ' . static::BLOG_TABLE . '_blog_category.blog_id = ' . static::BLOG_TABLE . '.blog_id'
            ),
            array('LEFT JOIN',
                static::BLOG_TABLE . '_category',
                'ON ' . static::BLOG_TABLE . '_blog_category.cat_id = ' . static::BLOG_TABLE . '_category.cat_id'
            ),
            // Join author
            array('LEFT JOIN', 'blog_author', 'ON blog_author.user_id = ' . static::BLOG_TABLE . '.user_id')
        );
    }

    protected function blogFields() {
        return array(
            static::BLOG_TABLE . '.*',
            'blog_author.*',
            'categories' => ['expression' => 'GROUP_CONCAT(category)']
        );
    }

    public function pagination() {
        // do noting if we don't have more than one page
        if (!$this->isList() || $this->post_count <= $this->list_per_page) {
            return false;
        }

        // set up some variables
        $pages = ceil($this->post_count / $this->list_per_page);

        if ($this->m > 0) {
            $base_link = "/blog/archive/{$this->y}/{$this->m}-%%.htm";
        } else if ($this->y > 0) {
            $base_link = "/blog/archive/{$this->y}-%%.htm";
        } else if (!empty($this->category)) {
            $base_link = '/blog/category/' . $this->category_url . '-%%.htm';
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

    public function recent_list($remote=false) {
        $list = static::getRecent();
        $target = $remote ? "target='_blank'" : '';
        if ($list->rowCount() > 0) {
            echo "<ul>";
            foreach($list as $r) {
                echo "<li><a href='/{$r['url']}.htm' {$target}>{$r['title']}</a></li>";
            }
            echo "</ul>";
        }
    }

    public static function getRecent() {
        return Database::getInstance()->select(static::BLOG_TABLE, [], [], 'ORDER BY time DESC LIMIT 5');
    }

    public function getAllCategories($order = 'count', $sort_direction = 'DESC') {
        return Database::getInstance()->select(
            array(
                'from' => static::BLOG_CATEGORY_TABLE,
                'join' => array('JOIN', static::CATEGORY_TABLE, 'USING (cat_id)'),
            ),
            array(),
            array(
                'count' => array('expression' => 'COUNT(*)'),
                'category',
                'cat_url'
            ),
            'GROUP BY cat_id ORDER BY `' . $order . '` ' . $sort_direction . ' LIMIT 10'
        );
    }

    public function categories_list() {
        $list = $this->getAllCategories();
        if ($list->rowCount() > 0) {
            echo "<ul>";
            foreach($list as $r)
                echo "<li><a href='/blog/category/". $r['cat_url'] . ".htm'>{$r['category']}</a> ({$r['count']})</li>";
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
    public function fetch_blog_url($url) {
        $this->loadContentByURL($url);
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
    public function fetch_blog_id($id) {
        $this->loadContentByID($id);
        if ($this->posts) {
            $this->id = $this->posts[0]['blog_id'];
        } else {
            $this->id = 0;
        }
    }

    public static function getSitemapUrls() {
        $web_root = Configuration::get('web_root');
        $blogs = Database::getInstance()->select([
            'from' => static::BLOG_TABLE,
        ],
            [],
            [
                [static::BLOG_TABLE => ['blog_time' => 'time']],
                'url',
            ],
            'GROUP BY blog_id'
        );

        $urls = array();
        foreach($blogs as $b) {
            $urls[] = array(
                'loc' => $web_root . "/{$b['url']}.htm",
                'lastmod' => date("Y-m-d", $b['blog_time'] ?: time()),
                'changefreq' => 'yearly',
                'priority' => .3,
            );
        }
        return $urls;
    }

    public function post_to_facebook($blog_data) {
        // @todo: this doesn't work'
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
        if ($image = $this->getFirstImage($blog_data['body'])) {
            $attachment['picture'] = $image;
        }
        $facebook->api(FACEBOOK_BLOG_PAGE . '/feed', 'post', $attachment);
    }

    public function getFirstImage($source) {
        preg_match_all('/<img\s+.*?src=[\"\']?([^\"\' >]*)[\"\']?[^>]*>/i', $source, $matches, PREG_SET_ORDER);
        if(!empty($matches[0][1])) {
            return (file_exists(HOME_PATH.$matches[0][1])) ? $matches[0][1] : NULL;
        }
        return null;
    }
}
