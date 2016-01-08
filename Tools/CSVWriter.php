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
    public function __construct($file = null) {
        if ($file == null) {
            // This will go to the stdout.
            $file = 'php://output';
            // We need an additional header.
            Output::setContentType('text/csv');
        }
        $this->file = fopen($file, 'w');
    }

    /**
     * Output a row.
     *
     * @param $values
     */
    public function writeRow($values) {
        fputcsv($this->file, $values);
    }

    /**
     * Initializes the file for downloading.
     *
     * @param $filename
     */
    public function setFilename($filename) {
        Output::download($filename);
    }
}
