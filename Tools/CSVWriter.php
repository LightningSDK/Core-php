<?php

namespace Lightning\Tools;

/**
 * Class CSVWriter
 * @package Lightning\Tools
 *
 * A streaming CSV writer that writes to stdout.
 */
class CSVWriter {

    /**
     * The output stream
     *
     * @var resource
     */
    protected $file;

    /**
     *
     */
    public function __construct() {
        $this->file = fopen('php://output', 'w');
    }

    /**
     * Output a row.
     *
     * @param $values
     */
    public function writeRow($values) {
        fputcsv($this->file, $values);
    }
}
