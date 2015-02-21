<?php

namespace Lightning\View;

class Help {
    public static function render($help_string, $image = '/images/lightning/qmark.png', $id = '', $class = '', $url = NULL) {
        if($url){
            echo "<a href='{$url}'>";
        }
        echo "<img src='{$image}' border='0' class='help {$class}' id='{$id}' />";
        echo "<div class='tooltip'>{$help_string}</div>";
        if($url){
            echo "</a>";
        }
    }
}
