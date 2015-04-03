<?php

namespace Lightning\Model;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;

class Page extends Object {

    public static function selectAllPages() {
        return Database::getInstance()->select('page', array('site_map' => 1));
    }

    public static function getSitemapUrls() {
        $urls = array();

        // Load the pages.
        $web_root = Configuration::get('web_root');
        $pages = static::selectAllPages();

        foreach($pages as $p) {
            if ($p['last_update'] == 0) {
                $p['last_update'] = time();
            }
            switch($p['frequency']) {
                case 0: $fr="daily"; break;
                case 1: $fr="weekly"; break;
                case 2: $fr="monthly"; break;
                case 3: $fr="annually"; break;
            }

            $urls[] = array(
                'loc' => $web_root . "/{$p['url']}.html",
                'lastmod' => date('Y-m-d', $p['last_update']),
                'changefreq' => $fr,
                'priority' => $p['priority'] / 100,
            );
        }

        return $urls;
    }
}
