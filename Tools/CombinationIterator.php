<?php

namespace Lightning\Tools;

use Iterator;

class CombinationIterator implements Iterator {

    protected $iterators;

    protected $positions;

    protected $more = true;

    /**
     * Construct a new combination iterator.
     *
     * @param array
     *   An array for each position in the iterator should be passed as an input.
     */
    public function __construct() {
        $this->iterators = func_get_args();
        foreach ($this->iterators as $k => $v) {
            if (!is_array($v)) {
                // Make sure a single value is wrapped in an array.
                $this->iterators[$k] = array($v);
            } else {
                // Make sure arrays are numeric.
                $this->iterators[$k] = array_values($v);
            }
        }
        $this->rewind();
    }

    /**
     * Determine if there is another item in the iterator.
     *
     * @return boolean
     *   Whether there is another item.
     */
    public function valid() {
        return $this->more && !empty($this->iterators);
    }

    /**
     * Move to the next position in the iterator.
     */
    public function next() {
        $this->positions[count($this->positions) - 1]++;
        for ($i = count($this->positions) - 1; $i > 0; $i--) {
            if ($this->positions[$i] == count($this->iterators[$i])) {
                $this->positions[$i] = 0;
                $this->positions[$i-1]++;
            }
        }
        if ($this->positions[0] == count($this->iterators[0])) {
            $this->more = false;
        }
    }

    /**
     * Get the current item.
     *
     * @return mixed
     *   The current item.
     */
    public function current() {
        $return = array();
        for ($i = 0; $i < count($this->positions); $i++) {
            $return[] = $this->iterators[$i][$this->positions[$i]];
        }
        return $return;
    }

    /**
     * Reset the iterator to it's first combination.
     */
    public function rewind() {
        $this->positions = array_fill(0, count($this->iterators), 0);
    }

    /**
     * Get the current key of the iterator.
     *
     * @return string
     */
    public function key() {
        // TODO: This could either be 1:2:3 or (1*3+2*2+3*1)
        return 0;
    }
}
