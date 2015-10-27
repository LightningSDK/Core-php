<?php

namespace Lightning\View;

use Lightning\Tools\Request;

class Pagination {
    protected $pages = 0;
    protected $rows = 0;
    protected $rowsPerPage = 25;
    protected $currentPage = 0;
    protected $parameter = 'page';
    protected $parameters = [];

    public function __construct($params = []) {
        if (!empty($params['page'])) {
            $this->currentPage = $params['page'];
        }
        if (!empty($params['rows'])) {
            $this->rows = $params['rows'];
        }
        if (!empty($params['rows_per_page'])) {
            $this->rowsPerPage = $params['rows_per_page'];
        }
        if (!empty($params['pages'])) {
            $this->pages = $params['pages'];
        }
        if (!empty($params['parameter'])) {
            $this->parameter = $params['parameter'];
        }
        if (!empty($params['parameters'])) {
            $this->parameters = $params['parameters'];
        }
        if (!empty($params['base_path'])) {
            $this->basePath = $params['base_path'];
        } else {
            $this->basePath = '/' . Request::getLocation();
        }

        if (empty($this->currentPage)) {
            $this->currentPage = Request::get($this->parameter, 'int') ?: 1;
        }

        $this->updateRowsPerPage();
    }

    public function setRowCount($row_count) {
        $this->rows = $row_count;
        $this->updateRowsPerPage();
    }

    public function setRowsPerPage($rows) {
        $this->rows = $rows;
        $this->updateRowsPerPage();
    }

    protected function updateRowsPerPage() {
        if (!empty($this->rows)) {
            $this->pages = ceil($this->rows / $this->rowsPerPage);
        }
    }

    public function render() {
        if (empty($this->pages)) {
            return '';
        }
        $output = '';
        $concatenator = strpos($this->basePath, '?') !== false ? '&' : '?';
        if ($this->pages > 1) {
            $output .= '<ul class="pagination">';
            $output .= '<li class="arrow ' . ($this->currentPage > 1 ? '' : 'unavailable') . '"><a href="' . $this->basePath . $concatenator . $this->parameter . '=' . 1 . '">&laquo; First</a></li>';
            for($i = max(1, $this->currentPage - 10); $i <= min($this->pages, $this->currentPage + 10); $i++) {
                if ($this->currentPage == $i) {
                    $output.= '<li class="current">' . $i . '</li>';
                } else {
                    $output.= "<li><a href='". $this->basePath . $concatenator . $this->parameter . '=' . $i ."'>{$i}</a></li>";
                }
            }
            $output .= '<li class="arrow ' . ($this->currentPage == $this->pages ? 'unavailable' : '') . '"><a href="' . $this->basePath . $concatenator . $this->parameter . '=' . $this->pages . '">Last &raquo;</a></li>';
            $output .= '</ul>';
        }
        return $output;
    }
}
