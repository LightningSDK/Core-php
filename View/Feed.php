<?php

namespace Lightning\View;

use Lightning\Tools\CSVWriter;
use Lightning\Tools\Request;

abstract class Feed extends Page {

    const NAME = 'feed';

    public abstract function load();
    public abstract function next($data);

    public function getCSVHeaders() {
        return null;
    }

    public function get() {
        $this->load();

        switch (Request::get('type')) {
            case 'json':
                return json_encode([]);
            case 'csv':
                $writer = new CSVWriter();
                $writer->setFilename(static::NAME . '.csv');

                if ($headers = $this->getCSVHeaders()) {
                    $writer->writeRow($headers);
                }

                foreach ($this->cursor as $row) {
                    $writer->writeRow($this->next($row));
                }

                exit;
        }
    }
}
