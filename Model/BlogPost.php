<?php

namespace Overridable\Lightning\Model;

use Lightning\Model\Object;
use Lightning\Pages\BlogTable;
use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\IO\FileManager;
use Lightning\View\HTML;
use Lightning\View\Text;

class BlogPost extends Object {
    const TABLE = 'blog';
    const PRIMARY_KEY = 'blog_id';

    const CATEGORY_TABLE = '_category';
    const BLOG_CATEGORY_TABLE = '_blog_category';
    const AUTHOR_TABLE = '_author';

    public static function loadPosts($where = [], $join = [], $limit = '') {
        return Database::getInstance()->selectAll(
            array(
                'from' => self::TABLE,
                'join' => array_merge($join, self::joinAuthorCatTables()),
            ),
            $where,
            array(
                static::TABLE . '.*',
                'blog_author.*',
                'categories' => ['expression' => 'GROUP_CONCAT(blog_blog_category.cat_id)']
            ),
            'GROUP BY ' . self::TABLE . '.blog_id ORDER BY time DESC ' . $limit
        );
    }

    protected static function joinAuthorCatTables() {
        return array(
            // Join categories
            array('LEFT JOIN',
                static::TABLE . '_blog_category',
                'ON ' . self::TABLE . '_blog_category.blog_id = ' . self::TABLE . '.blog_id'
            ),
            // Join author
            array('LEFT JOIN', 'blog_author', 'ON blog_author.user_id = ' . self::TABLE . '.user_id')
        );
    }

    public function getTrueHeaderImage() {
        if (!empty($this->header_image)) {
            // Image from upload.
            $field = BlogTable::getHeaderImageSettings();
            $fileHandler = FileManager::getFileHandler(empty($field['file_handler']) ? '' : $field['file_handler'], $field['container']);
            return $fileHandler->getWebURL($this->header_image);
        }
        return false;
    }

    public function getHeaderImage() {
        $header_image = NULL;
        if ($image = $this->getTrueHeaderImage()) {
            return $image;
        }
        elseif ($img = HTML::getFirstImage($this->body)) {
            // Image from post.
            $this->header_from_source = true;
            return $img;
        }
        else {
            // Default image.
            return Configuration::get('blog.default_image');
        }
    }

    public function getLink() {
        return '/' . $this->url . '.htm';
    }

    public function getURL() {
        return Configuration::get('web_root') . $this->getLink();
    }

    public function getAuthorLink() {
        return '/blog/author/' . $this->author_url;
    }

    public function getBody($force_short = false) {
        if ($this->shorten_body || $force_short) {
            return $this->getShortBody();
        } else {
            return $this->body;
        }
    }

    public function getShortBody($length = 250, $allow_html = true) {
        return Text::shorten($allow_html ? $this->body : strip_tags($this->body), $length);
    }

    public function getAuthorName() {
        return $this->author_name;
    }

    public function renderCategoryList() {
        $categories = explode(',', $this->categories);
        foreach ($categories as $cat): ?>
            <li>
                <a href="<?= $this->getCatLink($cat); ?>"><?= $this->getCatName($cat); ?></a>
            </li>
        <?php endforeach;
    }

    protected function getCatLink($cat) {
        $categories = BlogPost::getAllCategoriesIndexed();
        if (!empty($categories[$cat])) {
            return '/blog/category/' . $categories[$cat]['cat_url'] . '.htm';
        }
        return null;
    }

    protected function getCatName($cat) {
        $categories = BlogPost::getAllCategoriesIndexed();
        if (!empty($categories[$cat])) {
            return $categories[$cat]['category'];
        }
        return null;
    }

    public static function getCategory($search_value) {
        return Database::getInstance()->selectRow(
            self::TABLE . self::CATEGORY_TABLE,
            ['cat_url' => ['LIKE', $search_value]]
        );
    }

    public static function getAllCategoriesIndexed() {
        static $categories = [];

        if (empty($categories)) {
            $categories = Database::getInstance()->selectAllQuery([
                'from' => self::TABLE . self::CATEGORY_TABLE,
                'indexed_by' => 'cat_id',
                'order_by' => ['category' => 'ASC'],
            ]);
        }

        return $categories;
    }

    public static function getAllCategories($order = 'count', $sort_direction = 'DESC') {
        static $categories = [];
        if (empty($categories[$order][$sort_direction])) {
            $categories[$order][$sort_direction] = Database::getInstance()->selectAll(
                array(
                    'from' => self::TABLE . self::BLOG_CATEGORY_TABLE,
                    'join' => array('JOIN', self::TABLE . self::CATEGORY_TABLE, 'USING (cat_id)'),
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
        return $categories[$order][$sort_direction];
    }
}
