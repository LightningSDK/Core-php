<?php

namespace Lightning\View;

class Pagination {
    public static function render($base_path, $parameter, $current_page, $total_pages) {
        $output = '';
        if ($total_pages > 1) {
            $output .= '<ul class="pagination">';
            $output .= '<li class="arrow ' . ($current_page > 1 ? '' : 'unavailable') . '"><a href="' . $base_path . '?' . $parameter . '=' . 1 . '">&laquo; First</a></li>';
            for($i = max(1, $current_page - 10); $i <= min($total_pages, $current_page + 10); $i++) {
                if ($current_page == $i) {
                    $output.= '<li class="current">' . $i . '</li>';
                } else {
                    $output.= "<li><a href='". $base_path . '?' . $parameter . '=' . $i ."'>{$i}</a></li>";
                }
            }
            $output .= '<li class="arrow ' . ($current_page == $total_pages ? 'unavailable' : '') . '"><a href="' . $base_path . '?' . $parameter . '=' . $total_pages . '">Last &raquo;</a></li>';
            $output .= '</ul>';
        }
        return $output;
    }
}
