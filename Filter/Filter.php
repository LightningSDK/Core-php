<?php

namespace Lightning\Filter;

use Exception;

abstract class Filter implements FilterInterface {

    const TYPE = '';

    protected $settings = [];

    public function getSettings() {
        return $this->settings;
    }

    public function filterQuery(&$query, $values) {
        // Validate the parameters.
        $validated_values = [];
        foreach ($this->settings['options'] as $name => $options) {
            if ($options['type'] == 'select') {
                if (!isset($options['options'][$values[$name]])) {
                    throw new Exception('Missing or Invalid Parameter');
                } else {
                    $validated_values[$name] = $values[$name];
                }
            }
        }

        // Add the conditions to the query.
        if ($this->settings['type'] == 'operator_value') {
            if ($validated_values['operator'] == '=') {
                $query['where'][][$this->settings['field']] = $validated_values['value'];
            } else {
                $query['where'][][$this->settings['field']] = [$validated_values['operator'], $validated_values['value']];
            }
        }

        // Add the joins.
        if (!empty($this->settings['join'])) {
            // TODO: Make sure this table is not already joined.
            $query['join'][] = $this->settings['join'];
        }
    }
}

interface FilterInterface {
}
