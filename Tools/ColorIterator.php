<?php

namespace lightningsdk\core\Tools;

use Iterator;

class ColorIterator implements Iterator {

    const HEX = 1;
    const RGBA = 2;

    const LIGHT = 1;
    const TRANSPARENT = 2;

    protected $colors = [
        // Blue
        [151, 187, 205, 1],
        [187, 151, 205, 1],
        [205, 187, 151, 1],
        [187, 205, 151, 1],
        [151, 205, 187, 1],
        [205, 151, 187, 1],
    ];

    protected $position = 0;

    public function __construct() {

    }

    public function valid() {
        return true;
    }

    public function next() {
        $this->position = ($this->position + 1) % count($this->colors);
    }

    public function current($format = self::HEX, $variant = 0) {
        $color = $this->colors[$this->position];
        switch($variant) {
            case self::LIGHT:
                $color[0] = intval($color[0] + (255 - $color[0]) * .2);
                $color[1] = intval($color[1] + (255 - $color[1]) * .2);
                $color[2] = intval($color[2] + (255 - $color[2]) * .2);
                break;
            case self::TRANSPARENT:
                $color[3] = .2;
                break;
        }

        switch ($format) {
            case self::HEX:
                $output = '#';
                $output .= str_pad(dechex($color[0]), 2, '0');
                $output .= str_pad(dechex($color[1]), 2, '0');
                $output .= str_pad(dechex($color[2]), 2, '0');
                break;
            case self::RGBA:
                $output = 'rgba(' . implode(',', $color) . ')';
                break;
        }

        return $output;
    }

    public function rewind() {
        $this->position = 0;
    }

    public function key() {
        return $this->position;
    }
}
