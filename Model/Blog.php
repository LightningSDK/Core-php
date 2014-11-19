<?php

namespace Overridable\Lightning\Model;

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

    /**
     * @return Blog
     */
    public static function getInstance() {
        return parent::getInstance();
    }

    function body($body, $force_short = false){
        if ($this->shorten_body || $force_short) {
            return $this->short_body($body);
        } else {
            return $body;
        }
    }

    function short_body($body, $length = 250){
        $body = strip_tags($body);
        if (strlen($body) <= $length) return $body;

        $last_dot = strpos($body,". ",$length*.8);
        if ($last_dot >= 1 && $last_dot <= $length *1.2 )//go to the end of the sentence if it's less than 10% longer
            return substr($body,0,$last_dot+1);

        $last_white = strpos($body, " ", $length);
        if ($last_white >= $length)
            return substr($body,0,$last_white)."...";

        return $body;
    }

    function list_post(){
        $join = array();
        $where = array();
        if($this->y != 0){
            if($this->m > 0) // SELECT A MONTH
                $where['time'] = array('BETWEEN', mktime(0,0,0,$this->m,1,$this->y), mktime(0,0,0,$this->m+1,1,$this->y));
            else
                $where['time'] = array('BETWEEN', mktime(0,0,0,1,1,$this->y), mktime(0,0,0,1,1,$this->y+1));
        } else if($this->category != ''){
            $cat_id = Database::getInstance()->selectField('cat_id', static::CATEGORY_TABLE, array('cat_url' => array('LIKE', $this->category)));
            $join[] = array('JOIN', 'blog_blog_category', 'USING (blog_id)');
            $where['cat_id'] = $cat_id;
        }
        if($this->list_per_page > 0)
            $limit = " LIMIT ".intval(($this->page -1) * $this->list_per_page).", {$this->list_per_page}";
        $this->posts = Database::getInstance()->selectAll(
            array(
                'from' => static::BLOG_TABLE,
                'join' => $join,
            ),
            $where,
            array(),
            'ORDER BY time DESC ' . $limit
        );
        $this->post_count = Database::getInstance()->count(
            array(
                'from' => static::BLOG_TABLE,
                'join' => $join,
            ),
            $where
        );
    }

    function pagination(){
        // do noting if we dont have more than one page
        if($this->post_count <= $this->list_per_page) {
            return false;
        }

        // set up some variables
        $pages = ceil($this->post_count / $this->list_per_page);

        if($this->m > 0)
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
        for ($i = 1; $i <= $pages; $i++){
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

    function recent_list($remote=false){
        $list = Database::getInstance()->select(static::BLOG_TABLE, array(), array(), 'ORDER BY time DESC LIMIT 5');
        $target = $remote ? "target='_blank'" : '';
        if($list->rowCount() > 0){
            echo "<ul>";
            foreach($list as $r) {
                echo "<li><a href='/{$r['url']}.htm' {$target}>{$r['title']}</a></li>";
            }
            echo "</ul>";
        }
    }

    function recent_comment_list($remote=false){
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
        if($list->rowCount() > 0){
            echo "<ul>";
            foreach($list as $r)
                echo "<li><a href='/{$r['url']}.htm' {$target}>".$this->short_body($r['comment'],50)."...</a> in <a href='/{$r['url']}.htm'>{$r['title']}</a></li>";
            echo "</ul>";
        }
    }

    function categories_list(){
        $list = Database::getInstance()->select(
            array(
                'from' => static::BLOG_CATEGORY_TABLE,
                'join' => array('LEFT JOIN', 'blog_category', 'USING (cat_id)'),
            ),
            array(),
            array(
                'count' => array('expression' => 'COUNT(*)'),
                'category',
            ),
            'GROUP BY cat_id LIMIT 10'
        );
        if($list->rowCount() > 0){
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
     *   The blogs url.
     *
     * @return int
     *   The blog ID.
     */
    function fetch_blog_url($url){
        $this->posts = Database::getInstance()->selectAll(static::BLOG_TABLE, array('url' => $url));
        if($this->posts){
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
    function fetch_blog_id($id){
        $this->posts = Database::getInstance()->selectAll(static::BLOG_TABLE, array('blog_id' => $id));
        if($this->posts){
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
        if(!$this->show_unapproved_comments) {
            $conditions['approved'] = 1;
        }
        $this->posts[0]['comments'] = Database::getInstance()->selectAll(static::COMMENT_TABLE, $conditions);
    }
}
