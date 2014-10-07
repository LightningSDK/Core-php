<?php

namespace Lightning\Tools;

class CombinationIterator implements \Iterator {

    protected $iterators;

    protected $positions;

    protected $more = true;

    public function __construct() {
        $this->iterators = func_get_args();
        foreach ($this->iterators as $k => $v) {
            if (!is_array($v)) {
                $this->iterators[$k] = array($v);
            }
        }
        $this->rewind();
    }

    public function valid() {
        return $this->more && !empty($this->iterators);
    }

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

    public function current() {
        $return = array();
        for ($i = 0; $i < count($this->positions); $i++) {
            $return[] = $this->iterators[$i][$this->positions[$i]];
        }
        return $return;
    }

    public function rewind() {
        $this->positions = array_fill(0, count($this->iterators), 0);
    }

    public function key() {
        // TODO: This could either be 1:2:3 or (1*3+2*2+3*1)
        return 0;
    }
}
