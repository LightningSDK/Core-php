<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\Model\Page;
use lightningsdk\core\Model\Blog as BlogModel;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\View\API;

class Sitemap extends API {

    protected $urls = [];

    public function get() {
        print '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        foreach (Configuration::get('sitemap') as $class) {
            $urls = call_user_func([$class, 'getSitemapUrls']);
            print Output::XMLSegment($urls, 'url');
        }

        print '</urlset>';
        exit;
    }
}
