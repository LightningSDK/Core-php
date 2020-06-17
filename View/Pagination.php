<?php

namespace lightningsdk\core\View;

use lightningsdk\core\Tools\Request;

class Pagination {
    protected $pages = 0;
    protected $rows = 0;
    protected $rowsPerPage = 25;
    protected $currentPage = 0;
    protected $parameter = 'page';
    protected $parameters = [];
    protected $basePath;
    protected $basePathReplace;

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
        } elseif (!empty($params['base_path_replace'])) {
            $this->basePathReplace = $params['base_path_replace'];
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
        if (!empty($this->parameters)) {
            $this->basePath .= $concatenator . http_build_query($this->parameters);
            $concatenator = '&';
        }
        if ($this->pages > 1) {
            if (!empty($this->basePathReplace)) {
                list($prefix, $suffix) = explode('%%', $this->basePathReplace);
            } else {
                $prefix = $this->basePath . $concatenator . $this->parameter . '=';
                $suffix = '';
            }

            $output .= '<nav aria-label="Pagination"><ul class="pagination text-center">';
            $output .= '<li class="pagination-previous ' . ($this->currentPage > 1 ? '' : 'disabled') . '"><a href="' . $prefix . 1 . $suffix . '">First</a></li>';
            for($i = max(1, $this->currentPage - 5); $i <= min($this->pages, $this->currentPage + 5); $i++) {
                if ($this->currentPage == $i) {
                    $output.= '<li class="current"><span class="show-for-sr"></span>' . $i . '</li>';
                } else {
                    $output.= "<li><a href='". $prefix . $i . $suffix ."' aria-label='Page {$i}'>{$i}</a></li>";
                }
            }
            $output .= '<li class="pagination-next ' . ($this->currentPage == $this->pages ? 'disabled' : '') . '"><a href="' . $prefix . $this->pages . $suffix . '">Last</a></li>';
            $output .= '</ul></nav>';
        }
        return $output;
    }
}
