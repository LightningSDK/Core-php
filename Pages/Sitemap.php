<?php

namespace Lightning\Pages;

use Lightning\Tools\Configuration;
use Lightning\Tools\Database;
use Lightning\Tools\Output;
use Lightning\View\API;

class Sitemap extends API {
    public function get() {
        print '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        $db = Database::getInstance();
        $web_root = Configuration::get('web_root');
        $today = date("Y-m-d");
        $urls = array();

        // Load the pages.
        $pages = $db->select('page', array('site_map' => 1));

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

        $urls[] = array(
            'loc' => $web_root . '/directory',
            'lastmod' => $today,
            'changefreq' => 'weekly'
        );

        $blogs = $db->select([
                'from' => 'blog',
                'join' => [
                    'LEFT JOIN', 
                    ['from' => 'blog_comment', 'as' => 'blog_comment', 'fields' => ['time', 'blog_id'], 'order' => ['time' => 'DESC']],
                    'USING ( blog_id )'
                ],
            ],
            [],
            [
                ['blog' => ['blog_time' => 'time']],
                ['blog_comment' => ['blog_comment_time' => 'time']],
                'url',
            ],
            'GROUP BY blog_id'
        );

        foreach($blogs as $b) {
            $urls[] = array(
                'loc' => $web_root . "/{$b['url']}.htm",
                'lastmod' => date("Y-m-d", max($b['blog_time'],$b['blog_comment_time']) ?: time()),
                'changefreq' => 'yearly',
                'priority' => .3,
            );
        }

        print Output::XMLSegment($urls, 'url');
        print '</urlset>';
        exit;
    }
}
