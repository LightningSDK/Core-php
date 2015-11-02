<?php

namespace Lightning\View;

class Text {
    public static function shorten($body, $length = 250) {
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
}
