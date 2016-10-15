<?php

namespace Lightning\Pages;

use Lightning\Model\Page;
use Lightning\Model\Blog as BlogModel;
use Lightning\Tools\Output;
use Lightning\View\API;

class Sitemap extends API {

    protected $urls = [];

    public function get() {
        print '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        $this->loadUrls();
        print Output::XMLSegment($this->urls, 'url');

        print '</urlset>';
        exit;
    }

    protected function loadUrls() {
        $this->urls = array_merge(
            Page::getSitemapUrls(),
            BlogModel::getSitemapUrls()
        );
    }
}
