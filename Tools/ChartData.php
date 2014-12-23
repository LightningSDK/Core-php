<?php

namespace Lightning\Tools;

class ChartData {

    /**
     * The prepared data for output.
     * 
     * @var array
     */
    protected $data;
    
    public function __construct($start, $end) {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * Convert an array or query object into data sets.
     */
    public function createDataSets($data, $x = 'x', $y = 'y', $set_column = 'set') {
        $this->data = array();
        foreach ($data as $row) {
            if (!isset($this->data[$row[$set_column]])) {
                $this->data[$row[$set_column]] = array('data' => array_fill(0, $this->end - $this->start + 1, 0));
            }
            // Make sure it's within the range.
            if ($row[$x] - $this->start > 0 && $row[$x] - $this->start < $this->end - $this->start + 1) {
                // Add it in the place of the offset.
                $this->data[$row[$set_column]]['data'][$row[$x] - $this->start] = $row[$y];
            }
        }
    }

    public function setTitles($titles, $default = null) {
        foreach ($this->data as $key => &$set) {
            $set['label'] = !empty($titles[$key]) ? $titles[$key] : ($default ?: $key);
        }
    }

    public function setXLabels($labels) {
        $this->Xlabels = $labels;
    }

    public function getData() {
        return $this->data;
    }

    public function getSetKeys() {
        return array_keys($this->data);
    }

    /**
     * Output the data.
     * Terminates execution.
     */
    public function output() {
        $output = array(
            'datasets' => array_values($this->data)
        );
        if (!empty($this->Xlabels)) {
            $output['labels'] = $this->Xlabels;
        }
        Output::json($output);
    }
}
