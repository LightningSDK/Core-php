<?php

namespace Overridable\Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Scrub;
use Lightning\Tools\Singleton;

class Blog extends Singleton {

    var $id = 0;
    var $posts = array();
    var $shorten_body = false;
    var $show_unapproved_comments = false;
    var $y = 0;
    var $m = 0;
    var $category='';
    var $list_per_page = 10;
    var $page = 1;
    var $post_count;
    const BLOG_TABLE = 'blog';
    const CATEGORY_TABLE = 'blog_category';
    const BLOG_CATEGORY_TABLE = 'blog_blog_category';
    const COMMENT_TABLE = 'blog_comment';
    const AUTHOR_TABLE = 'blog_author';
    const IMAGE_PATH = 'img/blog';

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

    function body($body, $force_short = false) {
        if ($this->shorten_body || $force_short) {
            return $this->shortBody($body);
        } else {
            return $body;
        }
    }

    function shortBody($body, $length = 250) {
        $body = str_replace('<', ' <', $body);
        $body = strip_tags($body);
        if (strlen($body) <= $length) {
            return $body;
        }

        $last_dot = strpos($body, '. ', $length * .8);
        if ($last_dot >= 1 && $last_dot <= $length * 1.2 ) {
            //go to the end of the sentence if it's less than 10% longer
            return substr($body, 0, $last_dot + 1);
        }

        $last_white = strpos($body, ' ', $length);
        if ($last_white >= $length) {
            return substr($body, 0, $last_white) . '...';
        }

        return $body;
    }

    function list_post() {
        $join = array();
        $where = array();
        if ($this->y != 0) {
            if ($this->m > 0) // SELECT A MONTH
                $where['time'] = array('BETWEEN', mktime(0,0,0,$this->m,1,$this->y), mktime(0,0,0,$this->m+1,1,$this->y));
            else
                $where['time'] = array('BETWEEN', mktime(0,0,0,1,1,$this->y), mktime(0,0,0,1,1,$this->y+1));
        } elseif (!empty($this->category)) {
            $cat_id = Database::getInstance()->selectField('cat_id', static::CATEGORY_TABLE, array('cat_url' => array('LIKE', $this->category)));
            $join[] = array('JOIN', 'blog_blog_category', 'USING (blog_id)');
            $where['cat_id'] = $cat_id;
        }

        if ($this->list_per_page > 0) {
            $limit = " LIMIT ".intval(($this->page -1) * $this->list_per_page).", {$this->list_per_page}";
        }
        $this->posts = Database::getInstance()->selectAll(
            array(
                'from' => static::BLOG_TABLE,
                'join' => array_merge($join, $this->joinAuthorCatTables()),
            ),
            $where,
            $this->blogFields(),
            'GROUP BY ' . static::BLOG_TABLE . '.blog_id ORDER BY time DESC ' . $limit
        );
        $this->post_count = Database::getInstance()->count(
            array(
                'from' => static::BLOG_TABLE,
                'join' => $join,
            ),
            $where
        );
    }

    protected function getCategoryID($search_value) {
        return Database::getInstance()->selectField(
            'cat_id',
            static::CATEGORY_TABLE,
            array('cat_url' => $search_value)
        );
    }

    protected function getAuthorID($search_value) {
        return Database::getInstance()->selectField(
            'user_id',
            static::AUTHOR_TABLE,
            array('author_url' => $search_value)
        );
    }

    public function loadList($page, $search_field = null, $search_value = null) {
        $join = array();
        $where = array();
        if ($this->y != 0) {
            if ($this->m > 0) // SELECT A MONTH
                $where['time'] = array('BETWEEN', mktime(0,0,0,$this->m,1,$this->y), mktime(0,0,0,$this->m+1,1,$this->y));
            else
                $where['time'] = array('BETWEEN', mktime(0,0,0,1,1,$this->y), mktime(0,0,0,1,1,$this->y+1));
        } elseif (!empty($this->category)) {
            $cat_id = Database::getInstance()->selectField('cat_id', static::CATEGORY_TABLE, array('cat_url' => array('LIKE', $this->category)));
            $join[] = array('JOIN', 'blog_blog_category', 'USING (blog_id)');
            $where['cat_id'] = $cat_id;
        }

        if ($search_field == 'category') {
            if ($cat_id = $this->getCategoryID($search_value)) {
                $join[] = array(
                    'JOIN',
                    array('cat_search' => static::BLOG_TABLE . '_blog_category'),
                    'ON cat_search.blog_id = ' . static::BLOG_TABLE . '.blog_id'
                );
                $where['cat_search.cat_id'] = $cat_id;
            }
        }

        if ($search_field == 'author') {
            if ($author_id = $this->getAuthorID($search_value)) {
                $where[static::BLOG_TABLE . '.user_id'] = $author_id;
            }
        }

        if ($this->list_per_page > 0) {
            $limit = " LIMIT " . intval(($page -1) * $this->list_per_page) . ", {$this->list_per_page}";
        }

        $this->posts = Database::getInstance()->selectAll(
            array(
                'from' => static::BLOG_TABLE,
                'join' => array_merge($join, $this->joinAuthorCatTables()),
            ),
            $where,
            $this->blogFields(),
            'GROUP BY ' . static::BLOG_TABLE . '.blog_id ORDER BY time DESC ' . $limit
        );
        $this->postProcessResults();

        $this->post_count = Database::getInstance()->count(
            array(
                'from' => static::BLOG_TABLE,
                'join' => $join,
            ),
            $where
        );
    }

    public function loadContentByURL($url) {
        $url = preg_replace('/.htm$/', '', $url);
        $this->posts = Database::getInstance()->selectAll(
            array(
                'from' => static::BLOG_TABLE,
                'join' => $this->joinAuthorCatTables()
            ),
            array('url' => $url),
            $this->blogFields()
        );
        $this->postProcessResults();
    }

    public function loadContentByID($id) {
        $this->posts = Database::getInstance()->selectAll(
            array(
                'from' => static::BLOG_TABLE,
                'join' => $this->joinAuthorCatTables()
            ),
            array(static::BLOG_TABLE.'.blog_id' => $id),
            $this->blogFields()
        );
        $this->postProcessResults();
    }

    protected function postProcessResults() {
        foreach ($this->posts as &$post) {
            $post['categories'] = empty($post['categories']) ? array() : explode(',', $post['categories']);
        }

        //Header images
        foreach($this->posts as &$post) {
            $header_image = NULL;
            if(empty($post['header_image'])) {
                preg_match_all('/<img\s+.*?src=[\"\']?([^\"\' >]*)[\"\']?[^>]*>/i',$post['body'],$matches,PREG_SET_ORDER);
                if(!empty($matches[0][1])) {
                    $header_image = (file_exists(HOME_PATH.$matches[0][1]))?$matches[0][1]:NULL;
                }
            } else {
                $header_image = file_exists(HOME_PATH . '/' . self::IMAGE_PATH . '/' . $post['header_image'])
                    ? '/' . self::IMAGE_PATH . '/'.$post['header_image']
                    : NULL;
            }

            $post['header_image'] = $header_image;
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

    function pagination() {
        // do noting if we don't have more than one page
        if ($this->post_count <= $this->list_per_page) {
            return false;
        }

        // set up some variables
        $pages = ceil($this->post_count / $this->list_per_page);

        if ($this->m > 0)
            $base_link = "/archive/{$this->y}/{$this->m}-%%.htm";
        else if ($this->y > 0)
            $base_link = "/archive/{$this->y}-%%.htm";
        else if ($this->category != "")
            $base_link = "/category/".$this->create_url($r['category']).".htm";
        else
            $base_link = '/blog/page/%%';

        $output = '<ul class="pagination">';

        // Previous page link.
        $output .= '<li class="arrow' . ($this->page != 1 ? ' unavailable' : '') . '">
            <a href="' . str_replace('%%', $this->page - 1, $base_link) . '">&laquo;</a>';

        // Page numbers.
        for ($i = 1; $i <= $pages; $i++) {
            if ($i == $this->page) {
                $output .= '<li class="current"><a href="">' . $i . '</a></li>';
            } else {
                $output .= '<li><a href="' . str_replace('%%', $i, $base_link) .'">' . $i . '</a></li>';
            }
        }

        // Next page.
        $output .= '<li class="arrow' . ($pages <= $this->page ? ' unavailable' : '') . '">
            <a href="' . str_replace('%%', $this->page + 1, $base_link) . '">&raquo;</a>';

        $output .= '</ul>';
        return $output;
    }

    function recent_list($remote=false) {
        $list = Database::getInstance()->select(static::BLOG_TABLE, array(), array(), 'ORDER BY time DESC LIMIT 5');
        $target = $remote ? "target='_blank'" : '';
        if ($list->rowCount() > 0) {
            echo "<ul>";
            foreach($list as $r) {
                echo "<li><a href='/{$r['url']}.htm' {$target}>{$r['title']}</a></li>";
            }
            echo "</ul>";
        }
    }

    function recent_comment_list($remote=false) {
        $list = Database::getInstance()->select(
            array(
                'from' => static::COMMENT_TABLE,
                'join' => array('LEFT JOIN', 'blog', 'USING (blog_id)'),
            ),
            array(
                'approved' => array('>', 0),
            ),
            array(
                'url',
                'title',
                array('time' => array('expression' => 'blog_comment.time')),
                'comment',
            )
        );
        $target = $remote ? "target='_blank'" : '';
        if ($list->rowCount() > 0) {
            echo "<ul>";
            foreach($list as $r)
                echo "<li><a href='/{$r['url']}.htm' {$target}>".$this->shortBody($r['comment'],50)."...</a> in <a href='/{$r['url']}.htm'>{$r['title']}</a></li>";
            echo "</ul>";
        }
    }

    public function allCategories($order = 'count', $sort_direction = 'DESC') {
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

    function categories_list() {
        $list = $this->allCategories();
        if ($list->rowCount() > 0) {
            echo "<ul>";
            foreach($list as $r)
                echo "<li><a href='/category/". Scrub::url($r['category']) . ".htm'>{$r['category']}</a> ({$r['count']})</li>";
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
    function fetch_blog_url($url) {
        $this->loadContentByURL($url);
        if ($this->posts) {
            $this->id = $this->posts[0]['blog_id'];
            $this->loadComments();
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
    function fetch_blog_id($id) {
        $this->loadContentByID($id);
        if ($this->posts) {
            $this->id = $this->posts[0]['blog_id'];
            $this->loadComments();
        } else {
            $this->id = 0;
        }
    }

    /**
     * Load the current blog's comments.
     */
    protected function loadComments() {
        $conditions = array('blog_id' => $this->id);
        if (!$this->show_unapproved_comments) {
            $conditions['approved'] = 1;
        }
        $this->posts[0]['comments'] = Database::getInstance()->selectAll(static::COMMENT_TABLE, $conditions);
    }

    public static function getSitemapUrls() {
        $web_root = Configuration::get('web_root');
        $blogs = Database::getInstance()->select([
            'from' => static::BLOG_TABLE,
            'join' => [
                'LEFT JOIN',
                ['from' => static::COMMENT_TABLE, 'as' => static::COMMENT_TABLE, 'fields' => ['time', 'blog_id'], 'order' => ['time' => 'DESC']],
                'USING ( blog_id )'
            ],
        ],
            [],
            [
                [static::BLOG_TABLE => ['blog_time' => 'time']],
                [static::COMMENT_TABLE => ['blog_comment_time' => 'time']],
                'url',
            ],
            'GROUP BY blog_id'
        );

        $urls = array();
        foreach($blogs as $b) {
            $urls[] = array(
                'loc' => $web_root . "/{$b['url']}.htm",
                'lastmod' => date("Y-m-d", max($b['blog_time'],$b['blog_comment_time']) ?: time()),
                'changefreq' => 'yearly',
                'priority' => .3,
            );
        }
        return $urls;
    }
}
