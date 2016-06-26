<?php

namespace Lightning\View\HTMLEditor;

use DOMDocument;
use Lightning\Tools\Template;
use Lightning\View\Video\YouTube;

class Markup {
    public static function render($content) {
        $matches = [];
        preg_match_all('|{{.*}}|', $content, $matches);
        foreach ($matches[0] as $match) {
            if (!empty($match)) {
                // Convert to HTML and parse it.
                $match_html = '<' . trim($match, '{} ') . '/>';
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);
                $dom->loadHTML($match_html);
                $element = $dom->getElementsByTagName('body')->item(0)->childNodes->item(0);
                $output = '';
                switch ($element->nodeName) {
                    case 'template':
                        $sub_template = new Template();
                        $output = $sub_template->render($element->getAttribute('name'), true);
                        break;
                    case 'youtube':
                        $output = YouTube::render($element->getAttribute('id'), [
                            'autoplay' => $element->getAttribute('autoplay') ? true : false,
                        ]);
                        if ($element->getAttribute('flex')) {
                            $output = '<div class="flex-video ' . ($element->getAttribute('widescreen') ? 'widescreen' : '') . '">' . $output . '</div>';
                        }
                }
                $content = str_replace(
                    $match,
                    $output,
                    $content
                );
            }
        }
        return $content;
    }
}
