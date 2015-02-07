<?php

namespace Lightning\Tools;

use Iterator;

class CSVIterator implements Iterator {

    protected $position = 0;

    protected $fileHandler;

    protected $currentRow;

    protected $fileName;

    public function __construct($fileName) {
        $this->fileName = $fileName;
        $this->rewind();
    }

    /**
     * Determine if there is another item in the iterator.
     *
     * @return boolean
     *   Whether there is another item.
     */
    public function valid() {
        return !empty($this->currentRow);
    }

    /**
     * Move to the next position in the iterator.
     */
    public function next() {
        $this->currentRow = fgetcsv($this->fileHandler);
        $this->position++;
    }

    /**
     * Get the current item.
     *
     * @return mixed
     *   The current item.
     */
    public function current() {
        return $this->currentRow;
    }

    /**
     * Reset the iterator to it's first combination.
     */
    public function rewind() {
        $this->position = 0;
        $this->fileHandler = fopen($this->fileName, 'r');
        $this->currentRow = fgetcsv($this->fileHandler);
    }

    /**
     * Get the current key of the iterator.
     *
     * @return string
     */
    public function key() {
        return $this->position;
    }
}
