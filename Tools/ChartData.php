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

    /**
     * Set the titles for the existing sets.
     *
     * @param array $titles
     *   An array of strings keyed by the set_id.
     * @param string $default
     *   The default label if not in the $titles array.
     */
    public function setTitles($titles, $default = null) {
        foreach ($this->data as $key => &$set) {
            $set['label'] = !empty($titles[$key]) ? $titles[$key] : ($default ?: $key);
        }
    }

    /**
     * Make sure the data has an entry for each point in the range.
     *
     * @param array $data
     *   An array of arrays. The sub arrays are values keyed by range.
     *
     * @return array
     *   The conformed array.
     */
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

    /**
     * Make sure an array has all the required values for the full range.
     *
     * @param array $data
     *   The data keyed by the X value.
     *
     * @return array
     *   The data values.
     */
    public function conformSet($data) {
        $conformed_data = array_fill($this->start, $this->end - $this->start + 1, 0);
        foreach ($conformed_data as $key => $value) {
            if (!empty($data[$key])) {
                $conformed_data[$key] = $data[$key];
            }
        }
        return $conformed_data;
    }

    /**
     * Add a dataset.
     *
     * @param array $data
     *   A list of values keyed by the range point.
     * @param string $title
     *   The title of the set.
     * @param integer $previous_period
     *   The total amount of a previous period if showing a diff.
     * @param array $additional_params
     *   Additional values to add to the main dataset parameters.
     */
    public function addDataSet($data, $title = 'Unknown', $previous_period = 0, $additional_params = []) {
        $this->data[] = $additional_params + [
            'data' => array_values($data),
            'label' => $title,
            'previous' => $previous_period,
        ];
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

        $colors = new ColorIterator();
        foreach ($this->data as &$set) {
            $set += [
                'fillColor' => 'rgba(220,220,220,0.2)',//$colors->current(ColorIterator::RGBA, ColorIterator::TRANSPARENT),
                'strokeColor' => $colors->current(ColorIterator::RGBA),
                'pointColor' => $colors->current(ColorIterator::RGBA),
                'pointStrokeColor' => $colors->current(ColorIterator::HEX),
                'pointHighlightFill' => $colors->current(ColorIterator::HEX),
                'pointHighlightStroke' => $colors->current(ColorIterator::RGBA),
            ];
            $colors->next();
        }

        $output = array(
            'datasets' => array_values($this->data)
        );
        if (!empty($this->Xlabels)) {
            $output['labels'] = $this->Xlabels;
        }
        Output::jsonData($output);
    }
}
