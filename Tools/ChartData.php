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
        $this->data = array();
    }

    /**
     * Convert an array or query object into data sets.
     */
    public function createDataSets($data) {
        foreach ($data as $row) {
            if (!isset($this->data[$row['set']])) {
                $this->data[$row['set']] = array('data' => array_fill(0, $this->end - $this->start + 1, 0));
            }
            // Make sure it's within the range.
            if ($row['x'] - $this->start > 0 && $row['x'] - $this->start < $this->end - $this->start + 1) {
                // Add it in the place of the offset.
                $this->data[$row['set']]['data'][$row['x'] - $this->start] = $row['y'];
            }
        }
    }

    public function setTitles($titles, $default = null) {
        foreach ($this->data as $key => &$set) {
            $set['label'] = !empty($titles[$key]) ? $titles[$key] : ($default ?: $key);
        }
    }

    public function conformData($data) {
        $conformed_data = array_fill(0, $this->end - $this->start + 1, 0);
        foreach ($data as $row) {
            // Make sure it's within the range.
            if ($row['x'] - $this->start > 0 && $row['x'] - $this->start < $this->end - $this->start + 1) {
                // Add it in the place of the offset.
                $conformed_data[$row['x'] - $this->start] = $row['y'];
            }
        }
        return $conformed_data;
    }

    public function addDataSet($data, $title = 'Unknown') {
        $this->data[] = array(
            'data' => array_values($data),
            'label' => $title,
        );
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
        if (empty($this->data)) {
            $this->data[] = array();
        }
        $output = array(
            'datasets' => array_values($this->data)
        );
        if (!empty($this->Xlabels)) {
            $output['labels'] = $this->Xlabels;
        }
        Output::json($output);
    }
}
