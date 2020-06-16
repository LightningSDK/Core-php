<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\Tools\Request;
use lightningsdk\core\View\Page;
use lightningsdk\sitemanager\Model\Site;

class Robots extends Page {

    public function hasAccess() {
        return true;
    }

    public function get() {
        header('Content-Type:text/plain; charset=utf-8', true);
echo 'User-agent: *
Disallow: /user
Disallow: /admin
Disallow: /admin/

SITEMAP: ' . (Request::isHTTPS() ? 'https://' : 'http://') . Site::getInstance()->domain . '/sitemap';
        exit;
    }
}
