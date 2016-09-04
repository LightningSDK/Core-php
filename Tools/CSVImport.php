<?php

namespace Lightning\Tools;

use Exception;
use Lightning\Tools\Cache\FileCache;
use Lightning\View\Field\BasicHTML;
use Lightning\Model\Page;

class CSVImport extends Page {
    protected $fields;

    /**
     * These fields will be available to import but will not be added to the database unless they are listed in $processedFields
     *
     * @var array
     */
    protected $additionalFields;

    /**
     * A list of fields that will always be imported, even if not selected. This is for fields that are created during the validation process.
     *
     * @var array
     */
    protected $processedFields;
    protected $key;
    protected $table;
    protected $handlers = [];
    protected $values = [];

    /**
     * @var CSVIterator
     */
    protected $csv;

    /**
     * @var Database
     */
    protected $database;
    public $importAction = 'import';
    public $importAlignAction = 'import-align';

    /**
     * @var FileCache
     */
    protected $importCache;

    public function setFields($fields) {
        $this->fields = $fields;
    }

    public function setAdditionalFields($fields) {
        $this->additionalFields = $fields;
    }

    public function setProcessedFields($fields) {
        $this->processedFields = $fields;
    }

    public function setTable($table) {
        $this->table = $table;
    }

    public function setPrimaryKey($key) {
        $this->key = $key;
    }

    public function setHandler($handler, $callable) {
        $this->handlers[$handler] = $callable;
    }

    public function render() {
        if ($this->importCache) {
            return $this->renderAlignmentForm();
        } else {
            return $this->renderImportFile();
        }
    }

    public function renderImportFile() {
        return '<form action="" method="post" enctype="multipart/form-data">' . Form::renderTokenInput() . '<input type="hidden" name="' . $this->importAction . '" value="import"><input type="file" name="import-file" /><input type="submit" name="submit" value="Submit" class="button"></form>';
    }

    public function validate() {
        $this->loadCSVFromCache();
        $header_row = $this->csv->current();
        if (!$header_row) {
            throw new Exception('No file uploaded');
        }
    }

    protected function loadCSVFromCache($force = false) {
        if (empty($this->importCache) && $cache_key = Request::post('cache')) {
            $this->importCache = new FileCache();
            $this->importCache->loadReference($cache_key);
            if (!$this->importCache->isValid()) {
                throw new Exception('Invalid reference. Please try again.');
            }
        } elseif (empty($this->importCache)) {
            throw new Exception('Invalid reference. Please try again.');
        }

        if (empty($this->csv) || $force) {
            $this->csv = new CSVIterator($this->importCache->getFile());
        }
    }

    public function renderAlignmentForm() {
        $this->loadCSVFromCache();
        $header_row = $this->csv->current();
        $output = '<form action="" method="POST">' . Form::renderTokenInput();
        $output .= '<input type="hidden" name="action" value="' . $this->importAlignAction . '">';
        $output .= '<input type="hidden" name="cache" value="' . $this->importCache->getReference() . '" />';
        $output .= '<table><thead><tr><td>Field</td><td>From CSV Column</td></tr></thead>';

        $input_select = BasicHTML::select('%%', ['-1' => ''] + $header_row);

        $input_fields = array_merge($this->fields, $this->additionalFields);

        foreach ($input_fields as $field) {
            $field_string = $field;
            $display_name = ucfirst(str_replace('_', ' ', $field_string));

            if (is_array($field)) {
                $field_string = $field['field'];
                if (!empty($field['display_name'])) {
                    $display_name = $field['display_name'];
                }
            }

            if ($field_string != $this->key) {
                $output .= '<tr><td>' . $display_name . '</td><td>'
                    . preg_replace('/%%/', 'alignment[' . $field_string . ']', $input_select) . '</td></tr>';
            }
        }

        $output .= '</table><label><input type="checkbox" name="header" value="1" /> First row is a header, do not import.</label>';

        if (!empty($this->handlers['customImportFields']) && is_callable($this->handlers['customImportFields'])) {
            $output .= call_user_func($this->handlers['customImportFields']);
        } elseif (!empty($this->handlers['customImportFields'])) {
            $output .= $this->handlers['customImportFields'];
        }

        $output .= '<input type="submit" name="submit" value="Submit" class="button" />';

        $output .= '</form>';
        return $output;
    }

    /**
     * Load the uploaded import file into cache and parse it for input variables.
     */
    public function cacheImportFile() {
        // Cache the uploaded file.
        $this->importCache = new FileCache();
        $this->importCache->setName('table import ' . microtime());
        $this->importCache->moveFile('import-file');
    }

    /**
     * Process the data and import it based on alignment fields.
     */
    public function importDataFile() {
        $this->loadCSVFromCache();

        // Load the CSV, skip the first row if it's a header.
        if (Request::post('header', Request::TYPE_INT)) {
            $this->csv->next();
        }

        // Process the alignment so we know which fields to import.
        $alignment = Request::get('alignment', Request::TYPE_KEYED_ARRAY, Request::TYPE_INT);
        $fields = [];
        $additionalFields = [];
        foreach ($alignment as $field => $column) {
            if ($column != -1) {
                if (in_array($field, $this->fields)) {
                    // These fields are expected as input and will be inserted.
                    $fields[$field] = $column;
                } elseif (in_array($field, $this->additionalFields)) {
                    // These fields are expected as input but will not be inserted.
                    $additionalFields[$field] = $column;
                }
            }
        }

        // Get a list of fields that will be added to the database.
        $output_fields = array_merge(array_keys($fields), $this->processedFields);
        $output_fields = array_unique($output_fields);

        $this->database = Database::getInstance();

        $this->values = [];
        $rows = 0;

        // While there is another row available.
        while ($this->csv->valid()) {
            // Get the current row.
            $row = $this->csv->current();

            // Convert the row into field/value pairs.
            $validate_row = [];
            foreach ($fields as $field => $column) {
                $validate_row[$field] = $row[$column];
            }
            foreach ($additionalFields as $field => $column) {
                $validate_row[$field] = $row[$column];
            }

            // See if there is a validate handler.
            if (is_callable($this->handlers['validate'])) {

                // Call the validate method.
                if (!call_user_func_array($this->handlers['validate'], [&$validate_row])) {
                    $this->csv->next();
                    continue;
                }
            }

            // Add the current row to the import list.
            foreach ($output_fields as $field) {
                $this->values[$field][] = !empty($validate_row[$field]) ? $validate_row[$field] : '';
            }

            // If there are more than 100 rows, insert it before continuing.
            $rows++;
            if ($rows >= 100) {
                $this->processImportBatch();
                $this->values = [];
                $rows = 0;
            }

            $this->csv->next();
        }

        // If there are any values that haven't been inserted, insert now.
        if (!empty($this->values)) {
            $this->processImportBatch();
        }
    }

    protected function processImportBatch() {
        if (!empty($this->table)) {
            // This is a direct import to a database table.
            $last_id = $this->database->insertSets($this->table, $this->values, true);
            if (is_callable($this->handlers['importPostProcess'])) {
                $ids = $last_id
                    ? range($last_id, $last_id + $this->database->affectedRows() - 1)
                    : [];
                call_user_func_array($this->handlers['importPostProcess'], [&$this->values, &$ids]);
            }
        }
        elseif (!empty($this->handlers['importProcess']) && is_callable($this->handlers['importProcess'])) {
            call_user_func_array($this->handlers['importProcess'], [&$this->values]);
        }
        else {
            throw new Exception('No import method declared.');
        }
    }
}
