<?php

namespace Lightning\Pages;

use Exception;
use Lightning\Tools\Cache\FileCache;
use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\CSVImport;
use Lightning\Tools\Database;
use Lightning\Tools\Form;
use Lightning\Tools\Image;
use Lightning\Tools\IO\FileManager;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\PHP;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Template;
use Lightning\View\Field;
use Lightning\View\Field\BasicHTML;
use Lightning\View\Field\Checkbox;
use Lightning\View\Field\FileBrowser;
use Lightning\View\Field\Location;
use Lightning\View\Field\Time;
use Lightning\View\HTML;
use Lightning\View\HTMLEditor\HTMLEditor;
use Lightning\View\JS;
use Lightning\View\Page;
use Lightning\Tools\CSVWriter;
use Lightning\View\Pagination;
use Lightning\View\JSONEditor as JSONEditorView;

abstract class Table extends Page {

    /**
     * The primary key form the database.
     */
    const PRIMARY_KEY = '';

    /**
     * The table where the object is stored.
     */
    const TABLE = '';

    protected $page = ['table', 'Lightning'];

    protected $fullWidth = true;

    /**
     * @var Database
     *
     * A reference to the database for this table.
     */
    protected $database;

    /**
     * @deprecated - see const TABLE
     */
    protected $table;

    /**
     * @deprecated - see const PRIMARY_KEY
     */
    protected $key;

    protected $action;
    protected $function;

    /**
     * @var integer
     *
     * The primary key value of the current row.
     */
    protected $id = 0;

    /**
     * @var array
     *
     * A list of rows, or a list of values of a single row.
     */
    protected $list;

    protected $currentRow = 0;

    /**
     * A list of field definitions, to override the defaults read from the database.
     *
     * The array will be keyed by the field name. The value will either be a string
     * representing the field type, or an array with multiple options.
     *
     *   - type string - The type of the field. Options are:
     *     - date - 3 popups, m/d/y that are saved as a JD int
     *     - datetime - 6 popups, m/d/y h:m ap, saved as a unix timestamp
     *     - string - a single text input
     *     - html - an html input box
     *     - file - a file input
     *     - image - a file input with image processing options
     *   - hidden boolean - Will hide the field from all views
     *   - default mixed - Will use this as the default value when inserting. Will not be used if a value is supplied unless force_default_new is set to true. When creating a new entry, the field is visible, editable, and populated with this value.
     *   - value mixed - An absolute value that will always be used whether inserting or updating. This can not be overridden by user input.
     *   - force_default_new boolean - Forces new entries to use the default value. Prevents tampering with a field that has hidden and default set.
     *   - note string - Adds text under the field to help the user understand the input.
     *   - edit_value mixed - The value to show in the field while editing an existing entry. A value or callable. If a callable, the entire row will be passed as a parameter.
     *   - unlisted boolean - if set to true, this field will not appear on the list view
     *   - render_list_field - Will render the field in the list view.
     *   - render_edit_field - Will render the edit field. Must also render form fields if necessary.
     *   - location - for file and image types, the location is an absolute or relative directory of the storage location.
     *   - replace - for a file or image, whether the previous upload should be replaced.
     *   - browser boolean - whether to use the file browser for selection. If not, a file upload field will be used. Note that if using the browser, the file extension will be saved, but this is intended to change once the file browser supports multiple file sizes.
     *   - images array - a list of images for the image type. The key of this array is not used, and all elements from the main $field array are added to each image as default values.
     *     - original boolean - whether this is should be stored as the original, unmodified image
     *     - image_preprocess callable (source image resources) - A function that can modify the input image before other processes are called.
     *     - quality integer - the jpeg image compression quality (default 75).
     *     - image_postprocess callable (source image resources) - A function that can modify the input image after other processes are called.
     *     - keep_unprocessed boolean - if the image has not been modified and is destined for the same format, this will keep the image from recompressing. Default is false.
     *     - format string - The output format. Can be jpg or png. Default is jpg.
     *     - max_size integer - The maximum pixel dimensions in either X or Y axis.
     *     - max_width integer - The maximum pixel width. Larger than this will be scaled down.
     *     - max_height integer - The maximum pixel height. Larger than this will be scaled down.
     *     - width integer - An absolute width. Any other width will be scaled to this width.
     *     - height integer - An absolute height. Any other height will be scaled to this height.
     *     - crop string|array - A crop format. Can be any of the following:
     *          x - Will crop left and right only
     *          y - Will crop top and bottom only
     *          top
     *          bottom
     *          left
     *          right
     *          ['x' => (true|'left'|'right'), 'y' => (true|'top'|'bottom')]
     *     - alpha boolean - whether the output will have an alpha channel that needs to be preserved
     *     - background array - the background color when discarding an alpha channel. This will be in the format of an array of 3 integers [0, 0, 0,] to [255, 255, 255]
     *     - file_prefix string - prefixed to the stored file name (can include additional path info)
     *     - file_suffix - suffixed to the file name before the file extension.
     *   - lookup
     *     - lookuptable - the table to load the data
     *     - lookupkey - the key column for the data
     *     - display_column - the display column
     *   - insert_function - A function to calculate the value on insert.
     *       The input will be an array containing the values to be inserted into the database.
     *       It is expected to get it's input values directly from the request.
     *   - modified_function - A function to calculate the value on update
     *       See previous
     *   - submit_function - A function to calculate the value on update or insert
     *       See previous
     *   - insertable - whether a user can provide a value for this field when inserting. This will default to the same value as editable.
     *   - editable - whether a user can provide a value for this field when editing. Default is true.
     *
     * @var array
     */
    protected $preset = [];

    /**
     * Used when you want to set a value in the header (array).
     *
     * @var array
     */
    protected $template_vars;	//
    protected $trusted = false;
    protected $delconf = true;
    protected $action_file;
    protected $defaultAction = 'list';
    protected $defaultIdAction = 'view';
    protected $fields = [];

    /**
     * A list of many to many relationships.
     *
     * The array keys can be for reference only. There will be an intermediate table in the 'index' field
     * and the list of options will be referred to as the foreign table.
     *
     *   - accessControl - Injection into the $where query for available options for linking.
     *   - index - the name of a table for making many to many joins
     *   - table - the name of the table with the foreign data. if left empty, the key for this array will be used
     *   - key - the primary key of the foreign table
     *   - index_fkey - if the columns on the index table and foriegn table have different names, 'key' is the name of the column on the index table and 'index_fkey' is the name of column on the foreign table.
     *   - display_column - the column in the foreign table to display as the value
     *   - list - the way that the selected values should be displayed in list view
     *     - compact
     *
     * @var array
     */
    protected $links = [];
    protected $styles = [];

    /**
     * A list of default sorting fields.
     *
     * @var array
     */
    protected $sort = [];
    protected $maxPerPage = 25;
    protected $listCount = 0;
    protected $page_number = 1;

    /**
     * A list of columns that will be represented as buttons, checkboxes, etc.
     *
     * Each item in the action fields array should be an array with a list of settings.
     *
     *   - display_name - The name to show at the top of the column.
     *   - display_value - The text that will appear inside of a link or action field.
     *   - type - The type of the field.
     *     - link - A simple link to a new location ending with the line's primary key value
     *     - html - Fully rendered HTML. This can be a constant string or a callable function.
     *     - action - An action managed by the table, linking to the table handler page. IE /table?action=someAction&id=1, which will call the method getSomeAction() with $this->id = 1;
     *     - function - deprecated version of action.
     *     - checkbox - A rendered checkbox which will determine if this row is selected when applying an action to a group of rows.
     *   - condition - A function that, when passed the row's values, will return true or false as to whether the cell should be rendered.
     *   - action - If the type is action, this will be the name of the action in the link.
     *
     * @var array
     */
    protected $action_fields = [];
    protected $customTemplates = [];
    protected $list_where;

    protected $importable = false;
    /**
     * @var FileCache
     */
    protected $importCache;
    protected $importHandlers = [];

    /**
     * A list of extra fields to import.
     * These can be processed manually but will be selectable on the import page as if they were real columns
     * in the table.
     *
     * @var array
     */
    protected $additionalImportFields = [];

    /**
     * A list of fields that should be inserted, even if they are not listed.
     * In case additional fields are created during the validate process, they should be added here.
     *
     * @var array
     */
    protected $processedImportFields = [];

    /**
     * @var CSVImport
     */
    protected $CSVImporter;

    /**
     * Criteria of elements editable by this object.
     *
     * @var array
     */
    protected $accessControl = [];

    /**
     * This allows the edit form to return to a page other than the list view
     *
     * @var
     */
    protected $refer_return;

    /**
     * Set to true to allow for serial update
     *
     * @var
     */
    protected $enable_serial_update;

    /**
     * Set to true to automatically enter update mode on the next record when saving the current record.
     *
     * @var
     */
    protected $serial_update = false;
    protected $editable = true;
    protected $deleteable = true;
    protected $addable = true;
    protected $cancel = false;
    protected $searchable = false;

    /**
     * Add the ability to export a list.
     *
     * @var boolean
     */
    protected $exportable = false;

    /**
     * Add the ability to duplicate an entry.
     *
     * @var boolean
     */
    protected $duplicatable = false;

    /**
     * Whether the table is sortable.
     *
     * @var boolean
     */
    protected $sortable = true;

    protected $subset = [];
    protected $search_fields = [];
    protected $filters = [];
    protected $filterQuery;
    protected $searchWildcard = Database::WILDCARD_AFTER;
    protected $submit_redirect = true;
    protected $additional_action_vars = [];

    /**
     * Button names according to action type
     * @var array
     */
    protected $button_names = ['insert' => 'Insert', 'cancel' => 'Cancel', 'update' => 'Update'];

    /**
     * The list of actions perform after post request depending on type of the request
     * @var array
     */
    protected $action_after = ['insert' => 'list', 'update' => 'list'];
    protected $postActionAfter;

    /**
     * Extra buttons added to from. Array structure:
     * - type (type of the button out of available ones);
     * - text (text on the button);
     * - data (custom data);
     * - href (for link buttons)
     *
     * @var array
     */
    protected $custom_buttons = [];

    /**
     * Available custom button types
     */
    const CB_SUBMITANDREDIRECT = 1;
    const CB_LINK = 2;
    const CB_ACTION_LINK = 3;

    protected $function_after = [];
    protected $table_descriptions = "table_descriptions/";

    /**
     * If true, the user is only allowed to edit one row.
     *
     * @var boolean
     */
    protected $singularity = false;

    /**
     * The PK id of the row they can edit.
     *
     * @var integer
     */
    protected $singularityID = 0;
    protected $parentLink;

    /*
     * Joined table
     */
    // Joined table name
    protected $accessTable;
    protected $accessTableJoin;
    protected $accessTableWhere;

    protected $cur_subset;
    // Tables (and conditions) has been joined to general one
    protected $joins;
    // Fields we need to grab from joined table
    protected $joinFields = [];
    protected $header;
    protected $table_url;

    /**
     * Used when this table is editing child contents of a parent table.
     */
    protected $parentId;

    /**
     * To explicitly set the field order displayed. If this is set, all other fields will be ignored
     * when inserting/updating unless a behavior is explicitly set.
     *
     * @var array
     */
    protected $fieldOrder;
    protected $form_buttons_after;
    protected $rowClick;
    protected $update_on_duplicate_key = false;
    protected $post_actions;
    protected $readOnly = false;

    /**
     * A list of rows that will always be at the start of the table.
     *
     * @var array
     */
    protected $prefixRows;

    protected $updatedMessage;
    protected $createdMessage;

    public function __construct($options = []) {

        $this->database = Database::getInstance();

        // TODO: Remove this when the properties are removed:
        if (empty($this->table) && !empty(static::TABLE)) {
            $this->table = static::TABLE;
            $this->key = static::PRIMARY_KEY;
        }

        // TODO: Action is not set yet. Is any of this necessary?
        if ($this->action == 'new') {
            $backlinkname = '';
            $backlinkvalue = '';
            // check for a backlink to be prepopulated in a new entry
            if (isset($_REQUEST['backlinkname'])) $backlinkname = $_REQUEST['backlinkname'];
            if (isset($_REQUEST['backlinkvalue'])) $backlinkvalue = $_REQUEST['backlinkvalue'];
            // must have both
            if ($backlinkname && $backlinkvalue) {
                $this->preset[$backlinkname] = ['default' => $backlinkvalue];
            }
        }
        $this->function = Request::post('function');
        $this->id = Request::get('id');
        $this->page_number = max(1, Request::get('page', Request::TYPE_INT, '', 1));

        /*
         * serial_update comes as POST parameter
         */
        $this->serial_update = Request::post('serialupdate', Request::TYPE_BOOLEAN);

        $this->refer_return = Request::get('refer_return');

        $this->postActionAfter = Request::get('action-after');

        // load the sort fields
        if ($sort = Request::get('sort')) {
            $field = explode(';', $sort);
            $new_sort = [];
            foreach ($field as $f) {
                $f = explode(':', $f);
                if (!empty($f[1]) && $f[1] == 'D') {
                    $new_sort[$f[0]] = 'DESC';
                } else {
                    $new_sort[$f[0]] = 'ASC';
                }
            }
            $this->sort = $new_sort;
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $this->action_file = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
        }

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }

        $this->initSettings();

        $this->fillDetaultSettings();

        // Setup the template.
        $template = Template::getInstance();
        $template->set('table', $this);
        $template->set('full_width', true);

        parent::__construct();
    }

    protected function validateAccess($id) {
        if (!empty($this->accessControl)) {
            if (!$this->database->check(
                [
                    'from' => $this->table,
                    'join' => $this->getAccessTableJoins()
                ],
                array_merge($this->accessControl, [$this->getKey() => $id]))) {
                Output::accessDenied();
            }
        }
    }

    protected function initSettings() {}

    protected function fillDetaultSettings() {
        foreach ($this->links as $table => &$link_settings) {
            if (empty($link_settings['table'])) {
                $link_settings['table'] = $table;
            }
        }

        foreach ($this->search_fields as &$field) {
            $field = $this->fullField($field);
        }
    }

    public function get() {
        if ($this->singularity) {
            // The user only has access to a single entry. ID is irrelevant.
            $this->getEdit();
        } elseif (Request::query('id')) {
            $this->action = $this->defaultAction;
            if ($this->editable) {
                $this->getEdit();
            } else {
                $this->getView();
            }
        } else {
            $this->getList();
        }
    }

    public function getEdit() {
        $this->action = 'edit';
        $this->id = $this->singularity ? $this->singularityID : Request::query('id');

        if (!$this->id) {
            Output::error('Invalid ID');
        }
        if (!$this->editable) {
            Output::accessDenied();
        }
        $this->getRow();
    }

    public function getView() {
        $this->action = 'view';
        if (!$this->editable) {
            Output::accessDenied();
        }
        $this->getRow();
    }

    public function getNew() {
        $this->action = 'new';
        if (!$this->editable || !$this->addable) {
            Output::accessDenied();
        }
    }

    public function getDuplicate() {
        $this->action = 'duplicate';
        if (!$this->editable || !$this->addable || $this->singularity) {
            Output::accessDenied();
        }
        $this->id = Request::query('id');
        $this->getRow();
    }

    public function getPop() {
        if (!$this->editable || !$this->addable) {
            Output::accessDenied();
        }
        if ($pf = Request::get('pf')) {
            if (!isset($this->action)) {
                $this->action = 'new';
            }
            // Pop field table.
            $this->additional_action_vars['pf'] = $pf;
            // Pop field display name.
            $this->additional_action_vars['pfdf'] = Request::get('pfdf');
        }
    }

    /**
     * Export csv file with a list of current rows (including search results)
     */
    public function getExport() {
        $this->action = 'export';
        if (!$this->exportable) {
            Output::accessDenied();
        }

        // getting the full list of table data
        $this->loadMainFields();
        $this->maxPerPage = 10000;
        $this->loadList();

        // Initialize the export.
        $file = new CSVWriter();
        $file->setFilename($this->table . '_' . date('Y-m-d') . '.csv');

        // Build header row.
        $headrow = [];
        foreach ($this->fields as $field) {
            if ($this->whichField($field)) {
                $headrow[] = (!empty($field['display_name']) ? $field['display_name'] : $field['field']);
            }
        }

        // Output the header row.
        $file->writeRow($headrow);

        // Output the rows.
        foreach ($this->list as $row) {
            $datarow = [];
            foreach ($this->fields as $field) {
                // Hide hidden fields.
                if ($this->whichField($field)) {
                    $datarow[] = $this->printFieldValue($field, $row, false);
                }
            }
            $file->writeRow($datarow);
        }

        exit;
    }

    public function getPopReturn() {
        $this->action = 'pop_return';
    }

    /**
     * The initial form to ask for the upload file.
     */
    public function getImport() {
        $this->action = 'import';
        if (!$this->importable) {
            Output::accessDenied();
        }
        $this->CSVImporter = new CSVImport();
    }

    public function getId() {
        return $this->id;
    }

    /**
     * The file upload is posted here.
     */
    public function postImport() {
        $this->action = 'import-align';
        if (!$this->importable) {
            Output::accessDenied();
        }
        $this->initCSVImporter();
        $this->CSVImporter->cacheImportFile();
    }

    /**
     * The important alignment is posted, so process it here.
     */
    public function postImportAlign() {
        if (!$this->importable) {
            Output::accessDenied();
        }
        $this->initCSVImporter();
        $this->CSVImporter->importDataFile();
        Messenger::message('Import successful!');
        Navigation::redirect();
    }

    protected function initCSVImporter() {
        $this->CSVImporter = new CSVImport();
        $this->CSVImporter->setTable($this->table);
        $this->CSVImporter->setPrimaryKey($this->getKey());
        $this->CSVImporter->setFields(array_keys($this->get_fields($this->table, $this->preset)));
        $this->CSVImporter->setAdditionalFields($this->additionalImportFields);
        $this->CSVImporter->setProcessedFields($this->processedImportFields);
        foreach ($this->importHandlers as $name => $handler) {
            $this->CSVImporter->setHandler($name, $handler);
        }
    }

    public function getList() {
        if ($this->singularity) {
            $this->redirect();
        }
        $this->action = 'list';
    }

    /**
     * Ajax search, outputs HTML table replacement.
     */
    public function getSearch() {
        Output::setJson(true);
        $this->action = 'list';
        $this->loadMainFields();
        $this->loadList();
        Output::json(['html' => $this->renderList(), 'd' => Request::get('i', Request::TYPE_INT), 'status' => 'success']);
    }

    public function getDelete() {
        $this->action = 'delete';
        if (!$this->editable || !$this->deleteable) {
            Output::accessDenied();
        }
    }

    public function getAutocomplete() {
        Output::setJson(true);
        $field = Request::get('field');
        $type = Request::get('type');
        $search = Request::get('search');

        switch ($type) {
            case 'field':
                $this->loadMainFields();
                $autocomplete = $this->fields[$field]['autocomplete'];

                $where = [];
                if (!empty($autocomplete['search'])) {
                    if (!is_array($autocomplete['search'])) {
                        $autocomplete['search'] = [$autocomplete['search']];
                    }
                    $where = Database::getMultiFieldSearch($autocomplete['search'], explode(' ', $search));
                }
                $results = $this->database->selectIndexed(
                    $this->fields[$field]['autocomplete']['table'],
                    $autocomplete['field'],
                    $where,
                    [],
                    'LIMIT 50'
                );

                if (!empty($autocomplete['display_value']) && is_callable($autocomplete['display_value'])) {
                    array_walk($results, $autocomplete['display_value']);
                }
                break;

            case 'link':
                $link_settings = $this->links[$field];
                $results = $this->database->selectColumnQuery([
                    'select' => [$link_settings['key'], $link_settings['display_column']],
                    'from' => $link_settings['table'],
                    'where' => Database::getMultiFieldSearch([$link_settings['display_column']], explode(' ', $search)),
                    'limit' => 50,
                    'order_by' => $link_settings['display_column'],
                ]);
                break;
        }

        Output::json(['results' => $results]);
    }

    public function postCreateLink() {
        Output::setJson(true);

        $link = Request::get('link');

        if (empty($this->links[$link])) {
            throw new Exception('Invalid Link');
        }
        $settings = $this->links[$link];
        if (empty($settings['create'])) {
            throw new Exception('Can not create link');
        }

        $value = Request::get('value');

        if ($link = $this->database->selectField($settings['key'], $settings['table'], [$settings['display_column'] => ['LIKE', $value]])) {
            Output::json([
                'id' => $link,
                'new' => false,
            ]);
        } else {
            $id = $this->database->insert($settings['table'], [$settings['display_column'] => $value]);
            Output::json([
                'id' => $id,
                'new' => true,
                'value' => $value
            ]);
        }
    }

    public function postDelete() {
        $this->action = 'delconf';

        if ($_POST['submit'] == 'Yes') {
            // Make sure they have access.
            if (!$this->editable || !$this->addable) {
                Output::accessDenied();
            }

            // Loop through and delete any files.
            $this->loadMainFields();
            $this->getRow();
            foreach ($this->fields as $f => $field) {
                if (($field['type'] == 'file' || $field['type'] == 'image') && empty($field['browser'])) {
                    $fileHandler = $this->getFileHandler($field);
                    $fileHandler->delete($this->list[$field['field']]);
                }
            }

            // Delete the entry.
            $this->database->delete($this->table, [$this->getKey() => $this->id]);
        }

        // Redirect.
        $this->afterPostRedirect();
    }

    public function postInsert() {
        $this->action = 'insert';
        if (!$this->addable) {
            Output::accessDenied();
        }

        // Insert a new record.
        $this->loadMainFields();
        $values = $this->getFieldValues($this->fields);
        if ($values === false) {
            return $this->getNew();
        }
        if ($this->singularity) {
            $values[$this->singularity] = $this->singularityID;
        }
        $this->id = $this->database->insert($this->table, $values, $this->update_on_duplicate_key ? $values : true);
        if ($this->createdMessage !== false) {
            Messenger::message($this->createdMessage ?: 'The ' . $this->table . ' has been created.');
        }

        /*
         * Check if id is defined. If it's FALSE, there was an error
         * inserting the new row. Probably duplicating.
         */
        if ($this->id == FALSE) {
            Output::error('There was a conflict with an existing entry.');
        }

        $this->getRow();
        $this->afterInsert();
        $this->afterPost();

        $this->setPostedLinks();
        if (Request::get('pf')) {
            // if we are in a popup, redirect to the popup close script page
            Navigation::redirect($this->createUrl('pop-return', $this->id));
        }

        if (Request::get('lightning_table_duplicate', 'boolean')) {
            $this->afterDuplicate();
        }

        $this->afterPostRedirect();
    }

    protected function afterInsert() {}
    protected function afterUpdate() {}
    protected function afterPost() {}
    protected function afterDuplicate() {}

    public function postUpdate() {
        $this->id = Request::post('id');
        $this->action = 'update';
        if (!$this->editable) {
            Output::accessDenied();
        }

        $this->validateAccess($this->id);

        // Update the record.
        $this->loadMainFields();
        $this->getRow();
        $new_values = $this->getFieldValues($this->fields);
        if ($new_values === false) {
            return $this->getEdit();
        }

        if (!empty($new_values)) {
            $where = $this->accessRestrictions([$this->getKey() => $this->id]);
            $table = ['from' => $this->table, 'join' => $this->getAccessTableJoins()];
            $this->database->update($table, $new_values, $where);
        }
        $this->updateAccessTable();
        $this->setPostedLinks();

        if ($this->updatedMessage !== false) {
            Messenger::message($this->updatedMessage ?: 'The ' . $this->table . ' has been updated.');
        }

        // If serial update is set, set the next action to be an edit of the next higest key,
        // otherwise, go back to the list.
        $this->getRow();
        $this->afterUpdate();
        $this->afterPost();

        if ($this->enable_serial_update && $this->serial_update) {
            // Get the next id in the table
            $nextkey = $this->database->selectField(
                ['nextkey' => ['expression' => "MIN({$this->getKey()})"]],
                $this->table,
                [
                    $this->getKey() => ['>', $this->id]
                ]
            );
            if ($nextkey) {
                $this->id = $nextkey;
                $this->getRow();
                $this->action_after['update'] = 'edit';
            } else {
                // No higher key exists, drop back to the list
                $this->serial_update = false;
            }
        }

        $this->afterPostRedirect();
    }

    protected function accessRestrictions($where = []) {
        $where = $this->accessControl + $where;
        if ($this->singularity || $this->singularityID) {
            $where[] = [$this->getKey() => $this->singularityID];
        }
        return $where;
    }

    public function afterPostRedirect() {

        // Run any scripts after execution.
        if (isset($this->function_after[$this->action])) {
            $this->function_after[$this->action]();
        }

        // If this is a custom submit action.
        $submit = Request::get('submit');
        foreach ($this->custom_buttons as $button) {
            if ($button['text'] == $submit && !empty($button['redirect'])) {
                Navigation::redirect($this->replaceURLVariables($button['redirect']));
            }
        }

        // Redirect to the next page.
        if ($return = Request::get('table_return', 'url_encoded')) {
            Navigation::redirect($this->createUrl($return));
        }

        if ($this->submit_redirect && $redirect = Request::get('redirect')) {
            Navigation::redirect($redirect);
        } elseif ($action = Request::get('action-after')) {
            $this->redirect(['action' => $action, 'id' => $this->id]);
        } elseif (!empty($this->redirectAfter[$this->action])) {
            Navigation::redirect($this->createUrl($this->redirectAfter[$this->action]));
        } elseif ($this->submit_redirect && isset($this->action_after[$this->action])) {
            Navigation::redirect($this->createUrl(
                $this->action_after[$this->action],
                $this->action_after[$this->action] == 'list' ? 1 : $this->id)
            );
        } else {
            // Generic redirect.
            Navigation::redirect($this->createUrl());
        }
    }

    /**
     * Prepend the output by setting the page templates, etc.
     */
    public function output() {
        // Call finalize the output.
        parent::output();
    }

    /**
     * Set this table to readonly mode.
     *
     * @param boolean $readOnly
     *   Whether this should be read only.
     */
    public function setReadOnly($readOnly = true) {
        $this->editable = !$readOnly;
        $this->deleteable = !$readOnly;
        $this->addable = !$readOnly;
    }

    /**
     * Set an internal variable.
     *
     * @param string $var
     *   The variable name.
     * @param mixed $val
     *   The new value.
     */
    public function set($var, $val) {
        $this->$var = $val;
    }

    /**
     * Get the primary key for the table.
     *
     * @param boolean
     *   Whether to use table name for quering
     *
     * @return string
     *   The primary key name.
     */
    public function getKey($useTableName = FALSE) {
        if (empty($this->key) && !empty($this->table)) {
            $result = $this->database->query("SHOW KEYS FROM `{$this->table}` WHERE Key_name = 'PRIMARY'");
            $result = $result->fetch();
            $this->key = $result['Column_name'];
        }

        // When tables are joined we need to use table names to avoid key duplicating
        return $useTableName ? $this->fullField($this->key) : $this->key;
    }

    protected function fullField($field) {
        if (strpos($field, '.')) {
            return $field;
        } else {
            return $this->table . '.' . $field;
        }
    }

    public function render() {
        $output = '';
        $this->loadMainFields();
        $output .= $this->renderHeader();

        if ($this->action == 'new' && !$this->addable) {
            Output::accessDenied();
        }
        if ($this->action == 'edit' && !$this->editable) {
            Output::accessDenied();
        }

        if (!empty($this->renderHandler)) {
            $output .= $this->{$this->renderHandler}();
        } else {
            switch ($this->action) {
                case 'pop_return':
                    $this->renderPopReturn();
                    break;
                case 'view':
                    $output .= $this->renderActionHeader();
                case 'edit':
                case 'duplicate':
                case 'new':
                    $output .= $this->renderForm();
                    break;
                // DELETE CONFIRMATION
                case 'delete':
                    if (!$this->deleteable) {
                        Output::accessDenied();
                        break;
                    }
                    $this->confirmMessage = 'Are you sure you want to delete this?';
                    $output .= $this->renderConfirmation();
                    break;
                case 'import':
                    $output .= $this->CSVImporter->renderImportFile();
                    break;
                case 'import-align':
                    $output .= $this->CSVImporter->renderAlignmentForm();
                    break;
                case 'list':
                default:
                    $output .= $this->renderSearchForm();
                    $this->loadList();
                    $output .= $this->renderActionHeader();
                    $output .= '<div class="table_list">';
                    $output .= $this->renderList();
                    $output .= '</div>';
                    break;
            }
        }

        // TODO: update to use the JS class.
        // we shouldn't need to call this as long as we use the JS class.
        $this->js_init_data();

        return $output;
    }

    protected function renderSearchForm() {
        $output = '';
        if ($this->searchable) {
            // @todo namespace this
            JS::inline('table_search_i=0;table_search_d=0;');
            $output .= 'Search: <input type="text" name="table_search" id="table_search" value="' . Scrub::toHTML(Request::get('ste')) . '" />';
        }
        if (!empty($this->filters)) {
            $output .= '<div class="filters"></div><div class="text-right">Add Filter: ';
            $filter_options = ['' => ''];
            foreach ($this->filters as $filter_name => $options) {
                $filter = new $options['class']($options);
                $filter_options[$filter_name] = $filter->display_name;
                JS::set('table.filters.' . $filter_name, $filter->getSettings());
            }
            $output .= BasicHTML::select('filters', $filter_options) . '</div>';
        }
        return $output;
    }

    public function renderPopReturn() {
        $this->getRow();
        $send_data = [
            'pf' => Request::get('pf'),
            'id' => $this->id,
            'pfdf' => $this->list[Request::get('pfdf')]
        ];
        JS::startup('lightning.table.returnPop(' . json_encode($send_data) . ')');
    }

    public function renderConfirmation() {
        // get delete confirmation
        $output = "<br /><br />{$this->confirmMessage}<br /><br /><form action='' method='POST'>";
        $output .= Form::renderTokenInput();
        $output .= "<input type='hidden' name='id' value='{$this->id}' />
            <input type='hidden' name='action' value='{$this->action}' />
            <input type='submit' name='submit' value='Yes' class='button'/>
            <input type='submit' name='submit' value='No' class='button' />";
        if ($this->refer_return) {
            $output .= '<input type="hidden" name="refer_return" value="' . $this->refer_return . '" />';
        } else {
            $output .= "<input type='hidden' name='redirect' value='" . $this->getRedirectURL() . "' />";
        }
        $output .= "</form>";
        return $output;
    }

    public function getRedirectURL() {
        return preg_replace('|\?.*|', '', $_SERVER['REQUEST_URI']);
    }

    protected function renderHeader() {
        if (!empty($this->customTemplates[$this->action . '_header'])) {
            return $this->renderTemplate($this->customTemplates[$this->action . '_header']);
        } elseif (!empty($this->header)) {
            return '<h1>' . $this->header . '</h1>';
        }
        return '';
    }

    public function renderActionHeader() {
        $output = '';
        if (!empty($this->customTemplates[$this->action . '_action_header'])) {
            $output = $this->renderTemplate($this->customTemplates[$this->action . '_action_header']);
        } else {
            if ($this->addable && empty($this->singularity)) {
                $output .= "<a href='" . $this->createUrl('new') . "'><img src='/images/lightning/new.png' border='0' title='Add New' /></a>";
            }
            if ($this->importable) {
                $output .= "<a href='" . $this->createUrl('import') . "'><img src='/images/lightning/send_doc.png' border='0' title='Import' /></a>";
            }
            if ($this->exportable) {
                $output .= "<a href='{$this->createUrl('export')}' onclick='event.preventDefault(); lightning.table.export(this)'><img src='/images/lightning/detach.png' border='0' title='Export' /></a><br />";
            }
            $output .= $this->renderCustomHeaderButtons();
            $output .= '<br />';
        }
        return $output;
    }

    protected function renderCustomHeaderButtons() {
        $output = '';
        if (!empty($this->customHeaderButtons)) {
            foreach ($this->customHeaderButtons as $b => $data) {
                if (!empty($data['action'])) {
                    $url = $this->createUrl($data['action']);
                } elseif (!empty($data['url'])) {
                    $url = $data['url'];
                }

                if (!empty($data['content'])) {
                    $content = $data['content'];
                }
                $output .= "<a href='{$url}'>{$content}</a>";
            }
        }
        return $output;
    }

    public function renderList() {

        if ((
            (
                is_object($this->list) && get_class($this->list) == 'PDOStatement' && $this->list->rowCount() == 0)
                || (is_array($this->list) && count($this->list) == 0)
            ) && empty($this->prefixRows)) {
            return "<p></p><p></p><p>There is nothing to show. <a href='" . $this->createUrl('new') . "'>Add a new entry</a></p><p></p><p></p>";
        }

        if (!empty($this->customTemplates[$this->action . '_item'])) {
            return $this->renderTemplate($this->customTemplates[$this->action . '_item']);
        } else {
            // Show pagination
            // TODO: Move this into a template.
            $pagination = $this->getPagination();
            $output = $pagination->render();
            // if there is something to list
            if (count($this->list) > 0 || !empty($this->prefixRows)) {

                // add form if required
                if ($this->action_fields_requires_submit()) {
                    $output .= '<form action="' . $this->createUrl() . '" method="POST">';
                    $output .= Form::renderTokenInput();
                }
                $output .= '<div class="list_table_container">';
                $output .= '<table cellspacing="0" cellpadding="3" border="0" width="100%">';

                // SHOW HEADER
                $output .= '<thead><tr>';
                $output .= $this->renderListHeader();

                // SHOW ACTION HEADER
                $output .= $this->render_action_fields_headers();
                $output .= '</tr></thead>';

                // Initialize the click handler.
                if (!empty($this->rowClick)) {
                    switch ($this->rowClick['type']) {
                        case 'url':
                        case 'action':
                            JS::startup('$(".table_list").on("click", "tr", lightning.table.click)', ['/js/lightning.min.js']);
                            break;
                        case 'none':
                        default:
                            break;
                    }
                }

                $output .= '<tbody>';
                if (!empty($this->prefixRows)) {
                    $output .= $this->renderListRows($this->prefixRows, false);
                }
                if (count($this->list) > 0) {
                    $output .= $this->renderListRows($this->list, true);
                }
                $output .= '</tbody>';

                if ($this->action_fields_requires_submit()) {
                    $output .= '<input type="submit" name="submit" value="Submit" class="button medium" />';
                }
                $output .= '</table></div>';
                if ($this->action_fields_requires_submit())
                    $output .= '</form>';
                $output .= $pagination->render();
            }
            return $output;
        }
    }

    protected function renderHiddenTableFields() {
        $output = '';
        if ($this->postActionAfter) {
            $output = '<input type="hidden" name="action-after" value="' . Scrub::toHTML($this->postActionAfter) . '" />';
        }
        return $output;
    }

    protected function renderListHeader() {
        $output = '';
        // Determine which field order to use.
        $field_order = is_array($this->fieldOrder) ? $this->fieldOrder
            : array_merge(array_keys($this->fields), array_keys($this->links));

        foreach ($field_order as $f) {
            if (!empty($this->fields[$f])) {
                $display_name = $this->getDisplayName($this->fields[$f], $f);
                if ($this->whichField($this->fields[$f])) {
                    if ($this->sortable)
                        $output .= "<td><a href='" . $this->createUrl('list', 0, '', ['sort'=> [$f => 'X']]) . "'>{$display_name}</a></td>";
                    else
                        $output .= "<td>{$display_name}</td>";
                }
            }
            elseif (!empty($this->links[$f])) {
                $display_name = $this->getDisplayName($this->links[$f], $f);
                if (!empty($this->links[$f]['list']) && $this->links[$f]['list'] == 'compact') {
                    $output .= "<td>{$display_name}</td>";
                }
            }
            else {
                throw new Exception('Invalid field');
            }
        }

        return $output;
    }

    protected function getDisplayName($field, $field_name) {
        if (isset($field['display_name'])) {
            return $field['display_name'];
        } elseif (isset($field['display_value'])) {
            return $field['display_value'];
        } else {
            return ucwords(str_replace('_', ' ', $field_name));
        }
    }

    protected function renderListRows($list, $editable) {
        $output = '';
        // loop through DATA rows
        foreach ($list as $row) {
            // prepare click action for each row
            $output .= "<tr id='{$row[$this->getKey()]}'>";
            // SHOW FIELDS AND VALUES
            $field_order = is_array($this->fieldOrder) ? $this->fieldOrder
                : array_merge(array_keys($this->fields), array_keys($this->links));

            foreach ($field_order as $f) {
                // Fields
                if (!empty($this->fields[$f]) && $this->whichField($this->fields[$f])) {
                    if (!empty($this->fields[$f]['align'])) {
                        $output .= "<td align='{$this->fields[$f]['align']}'>";
                    } else {
                        $output .= '<td>';
                    }
                    $output .= $this->printFieldValue($this->fields[$f], $row);
                    $output .= '</td>';
                }

                // Links
                elseif (!empty($this->links[$f])) {
                    // List all links in one cell.
                    $output .= $this->renderLinkCell($this->links[$f], $row, $f);
                }
            }

            // EDIT, DELETE, AND OTHER ACTIONS
            $output .= $this->render_action_fields_list($row, $editable);

            // CLOSE MAIN DATA ROW
            $output .= "</tr>";

            // LINKS EACH ITEM GETS ITS OWN ROW
            $output .= $this->render_linked_table($row);
        }
        return $output;
    }

    protected function action_fields_requires_submit() {
        foreach ($this->action_fields as $a => $action) {
            if ($action['type'] == "checkbox") return true;
        }
    }

    // Render a link cell
    protected function renderLinkCell(&$link_settings, &$row, $link) {
        $output = '';
        if (!empty($link_settings['list']) && $link_settings['list'] == 'compact') {
            if (!empty($link_settings['index'])) {
                // There is a link table joining them. (Many to many) {
                $links = $this->load_all_active_list($link_settings, $row[$this->getKey()]);
            }
            else {
                $links = $this->database->select($link, [$this->getKey() => $row[$this->getKey()]]);
            }

            $output .= '<td>';
            $displays = [];
            if (isset($link_settings['list']) == 'compact') {
                foreach ($links as $l)
                    if (!empty($link_settings['fields']) && is_array($link_settings['fields'])) {
                        $display = $link_settings["display"];
                        foreach ($link_settings['fields'] as $f => $a) {
                            if (!isset($a['field'])) $a['field'] = $f;
                            $display = str_replace('{' . $f . '}', $this->printFieldValue($a, $l), $display);
                        }
                        $displays[] = $display;
                    } else {
                        $displays[] = $l[$link_settings['display_column']];
                    }
                if (!isset($link_settings['seperator'])) {
                    $link_settings['seperator'] = ', ';
                }
                $output .= implode($link_settings['seperator'], $displays);
            }
            $output .= '</td>';
        }
        return $output;
    }

    protected function getMainTableColumnCount() {
        if (empty($this->mainTableColumnCount)) {
            $this->mainTableColumnCount = count($this->fields);
            if ($this->editable) {
                $this->mainTableColumnCount++;
            }
            if ($this->deleteable) {
                $this->mainTableColumnCount++;
            }
        }
        return $this->mainTableColumnCount;
    }

    // called when rendering lists
    protected function render_linked_table(&$row) {
        $output = "<tr class='linked_list_container_row'><td colspan='" . $this->getMainTableColumnCount() . "'><table width='100%'>";
        foreach ($this->links as $link => $link_settings) {
            if (!empty($link_settings['list']) && $link_settings['list'] === "each") {
                $link_settings['fields'] = $this->get_fields($link_settings['table'], $link_settings['preset']);
                $links = $this->load_all_active_list($link_settings, $row[$this->getKey()]);

                // Set the character to join the URL parameters to the edit_link
                $joinchar = (!empty($link_settings['edit_link']) && strpos($link_settings['edit_link'], "?") !== false) ? '&' : '?';

                if (!empty($link_settings['display_header'])) {
                    $output .= "<tr class='linked_list_header'><td>{$link_settings['display_header']}";
                }
                if (!empty($link_settings['edit_link'])) {
                    $output .= " <a href='{$link_settings['edit_link']}{$joinchar}action=new&backlinkname={$this->getKey()}&backlinkvalue={$row[$this->getKey()]}'>New</a>";
                }
                if (!empty($link_settings['edit_js'])) {
                    // TODO: Move this to an init function.
                    $output .= " <a href='' onclick='{$link_settings['edit_js']}.newLink({$row[$this->getKey()]})'>New</a>";
                }
                $output .= "</td></tr>";
                foreach ($links as $row) {
                    $output .= "<tr id='link_{$link}_{$row[$link_settings['key']]}' class='linked_list_row'>";
                    foreach ($link_settings['fields'] as $field_name => $field) {
                        $output .= '<td>' . $this->printFieldValue($field, $row) . '</td>';
                    }
                    if (!empty($link_settings['edit_link'])) {
                        $output .= "<td><a href='{$link_settings['edit_link']}{$joinchar}action=edit&id={$row[$link_settings['key']]}'>Edit</a> <a href='{$link_settings['edit_link']}{$joinchar}action=delete&id={$row[$link_settings['key']]}'><img src='/images/lightning/remove.png' border='0' /></a></td>";
                    }
                    if (!empty($link_settings['edit_js'])) {
                        $output .= "<td><a href='' onclick='{$link_settings['edit_js']}.editLink({$row[$link_settings['key']]})'>Edit</a> <a href='' onclick='{$link_settings['edit_js']}.deleteLink({$row[$link_settings['key']]})'><img src='/images/lightning/remove.png' border='0' /></a></td>";
                    }
                    $output .= "</tr>";
                }
            }
        }
        $output .= "</table></td></tr>";
        return $output;
    }

    protected function render_action_fields_headers() {
        $output = '';
        foreach ($this->action_fields as $a => $action) {
            $output .= '<td>';
            $output .= $this->getDisplayName($action, $a);
            switch ($action['type']) {
                case 'link':
                case 'html':
                case 'action':
                case 'function':
                    break;
                case 'checkbox':
                default:
                    if (!isset($action['check_all']) || empty($action['check_all'])) {
                        $output .= "<input type='checkbox' name='taf_all_{$a}' id='taf_all_{$a}' value='1' onclick=\"lightning.table.selectAll('{$a}');\" />";
                    }
                    break;
            }
            $output .= '</td>';
        }
        if ($this->editable !== false) {
            $output .= '<td>Edit</td>';
        }
        if ($this->duplicatable !== false) {
            $output .= '<td>Duplicate</td>';
        }
        if ($this->deleteable !== false) {
            $output .= '<td>Delete</td>';
        }
        return $output;
    }

    protected function render_action_fields_list(&$row, $editable) {
        $output = '';
        foreach ($this->action_fields as $a => $action) {
            $output .= '<td>';
            // Get the display value for the column.
            $link_content = isset($action['display_value']) ? $action['display_value'] : $this->getDisplayName($action, $a);

            // Run a custom condition to see if this should be displayed at all.
            if (!empty($action['condition']) && is_callable($action['condition'])) {
                if (!$action['condition']($row)) {
                    $output .= '</td>';
                    continue;
                }
            }
            switch ($action['type']) {
                case 'function':
                    // Have table call a function.
                    $output .= "<a href='" . $this->createUrl("action", $row[$this->getKey()], $a, ['ra' => $this->action]) . "'>{$link_content}</a>";
                    break;
                case 'link':
                    $output .= "<a href='{$action['url']}{$row[$this->getKey()]}'>{$link_content}</a>";
                    break;
                case 'html':
                    // Render the HTML.
                    $output .= is_callable($action['html']) ? $action['html']($row) : $action['html'];
                    break;
                case 'action':
                    $output .= "<a href='" . $this->createUrl($action['action'], $row[$this->getKey()], !empty($action['action_field']) ? $action['action_field'] : '') . "'>{$link_content}</a>";
                    break;
                case 'checkbox':
                default:
                    $output .= "<input type='checkbox' name='taf_{$a}[{$row[$this->getKey()]}]' class='taf_{$a}' value='1' />";
                    break;
            }
            $output .= '</td>';
        }
        if ($this->editable !== false) {
            $output .= '<td>';
            if ($editable) {
                $output .= "<a href='" . $this->createUrl("edit", $row[$this->getKey()]) . "'><img src='/images/lightning/edit.png' border='0' /></a>";
            }
            $output .= '</td>';
        }
        if ($this->duplicatable !== false) {
            $output .= "<td>";
            if ($editable) {
                $output .= "<a href='" . $this->createUrl("duplicate", $row[$this->getKey()]) . "'><img src='/images/lightning/duplicate.png' border='0' /></a>";
            }
            $output .= "</td>";
        }
        if ($this->deleteable !== false) {
            $output .= "<td>";
            if ($editable) {
                $output .= "<a href='" . $this->createUrl("delete", $row[$this->getKey()]) . "'><img src='/images/lightning/remove.png' border='0' /></a>";
            }
            $output .= "</td>";
        }
        return $output;
    }

    /**
     * Render the entire edit/create form.
     *
     * @return string
     *   The fully rendered HTML content.
     */
    protected function renderForm() {
        if (!empty($this->customTemplates[$this->action . '_item'])) {
            // Render the form using HTML templates.
            return $this->renderTemplate($this->customTemplates[$this->action . '_item']);
        } else {
            // Render the form in a table as basic HTML.
            // TODO: Put this into a template.
            $output = $this->renderFormOpen();

            // Header options.
            if ($this->action == "view" && !$this->readOnly) {
                if ($this->editable !== false) {
                    $output .= "<a href='" . $this->createUrl('edit', $this->id) . "'><img src='/images/lightning/edit.png' border='0' /></a>";
                }
                if ($this->deleteable !== false) {
                    $output .= "<a href='" . $this->createUrl('delete', $this->id) . "'><img src='/images/lightning/remove.png' border='0' /></a>";
                }
            }
            $style = !empty($this->styles['form_table']) ? "style='{$this->styles['form_table']}'" : '';
            $output .= '<table class="table_form_table" ' . $style . '>';

            $hidden_fields = [];
            $fieldOrder = is_array($this->fieldOrder) && !empty($this->fieldOrder) ? $this->fieldOrder : array_keys($this->fields);
            foreach ($fieldOrder as $f) {
                if (!empty($this->fields[$f]['type']) && $this->fields[$f]['type'] == 'hidden') {
                    if (!empty($this->fields[$f]['Value'])) {
                        $hidden_fields[] = BasicHTML::hidden($f, $this->fields[$f]['Value']);
                    }
                } else {
                    $output .= $this->renderFormRow($this->fields[$f], $this->list);
                }
            }

            $output .= $this->render_form_linked_tables();

            // Render all submission buttons
            $output .= '<tr><td colspan="2">' . $this->renderButtons() . '</td></tr>';

            $output .= implode('', $hidden_fields);

            $output .= '</table>';
            $output .= $this->renderFormClose();

            return $output;
        }
    }

    public function renderFormOpen() {
        $output = '';

        if ($this->action != 'view') {
            $multipart_header = $this->hasUploadfield() ? 'enctype="multipart/form-data"' : '';
            $output .= '<form action="' . $this->createUrl() . '" id="form_' . $this->table . '" method="POST" ' . $multipart_header . '><input type="hidden" name="action" id="action" value="' . $this->getNewAction() . '" />';
            if ($this->action == 'duplicate') {
                $output .= '<input type="hidden" name="lightning_table_duplicate" value="' . $this->id . '" />';
            }
            $output .= Form::renderTokenInput();
            $output .= $this->renderHiddenTableFields();
            if ($return = Request::get('return', 'urlencoded')) {
                $output .= BasicHTML::hidden('table_return', $return);
            }
        }
        // use the ID if we are editing a current one
        if ($this->action == "edit") {
            $output .= '<input type="hidden" name="id" id="id" value="' . $this->id . '" />';
        }

        return $output;
    }

    public function renderFormClose() {
        if ($this->action != 'view') {
            return '</form>';
        }
        return '';
    }

    protected function getNewAction() {
        if ($this->action == 'new' || $this->action == 'duplicate') {
            return 'insert';
        } else {
            return 'update';
        }
    }

    public function renderButtons() {
        $output = '';

        if ($this->action != 'view') {
            // Submit button has name parameter as 'sbmt' by purpose. When it is 'submit', form doesn't submit by javascript.
            $output .= '<input type="submit" name="sbmt" value="' . $this->button_names[$this->getNewAction()] . '" class="button medium">';
        }

        // If exist render all custom buttons
        $output .= $this->renderCustomButtons();

        if ($this->action != 'view') {
            if ($this->cancel) {
                $output .= '<input type="button" name="cancel" value="' . $this->button_names['cancel'] . '" onclick="document.location=\'' . $this->createUrl() . '\';" />';
            }
            if ($this->refer_return) {
                $output .= '<input type="hidden" name="refer_return" value="' . $this->refer_return . '" />';
            }
            if ($this->getNewAction() == 'update' && $this->enable_serial_update) {
                $output .= '<input type="checkbox" name="serialupdate" value="true" checked="checked" /> Edit Next Record';
            }
            $output .= $this->form_buttons_after;
        }
        if ($this->action == 'view' && !$this->readOnly) {
            if ($this->editable !== false) {
                $output .= '<a href="' . $this->createUrl('edit', $this->id) . '"><img src="/images/lightning/edit.png" border="0" /></a>';
            }
            if ($this->deleteable !== false) {
                $output .= '<a href="' . $this->createUrl('delete', $this->id) . '"><img src="/images/lightning/remove.png" border="0" /></a>';
            }
        }

        return $output;
    }

    /**
     * Outputs custom buttons depending on its type
     *
     * @return boolean
     *   If there's no custom buttons, just exit the function
     */
    protected function renderCustomButtons() {
        $output = '';

        if (empty($this->custom_buttons)) {
            return FALSE;
        }

        /*
         * In case of there're a few buttons, set the different ids to them
         * by adding a postfix
         */
        $button_id = 0;
        foreach ($this->custom_buttons as $button) {
            if ($this->action == 'view' && empty($button['view'])) {
                continue;
            }
            // Id for a single button
            $button_id++;
            // Check the type and render
            switch ($button['type']) {
                case self::CB_SUBMITANDREDIRECT:
                    // Submit & Redirect button
                    $output .= $this->renderSubmitAndRedirect($button, $button_id);
                    break;
                case self::CB_ACTION_LINK:
                case self::CB_LINK:
                    $attributes = [
                        'href' => $button['url'],
                        'class' => 'button medium',
                    ];

                    if ($button['type'] == self::CB_ACTION_LINK) {
                        $attributes['href'] .= (strpos($attributes['href'], '?') !== false ? '&' : '?')
                            . 'id' . self::PRIMARY_KEY . '=' . $this->id;
                    }

                    if (!empty($button['download'])) {
                        $attributes['download'] = $button['download'];
                    }
                    if (!empty($button['target'])) {
                        $attributes['target'] = $button['target'];
                    }
                    $output .= '<a ' . HTML::implodeAttributes($attributes) . '>' . $button['text'] . '</a>';
            }
        }
        return $output;
    }

    /**
     * Renders button which submit the form and redirect user to a specified
     * link
     *
     * @param array $button
     *   Button data
     * @param string $button_id
     *   Button id which would be used and postfix in html id parameter
     *
     * @return string
     */
    protected function renderSubmitAndRedirect($button, $button_id) {
        // Output the button.
        return "<input id='custombutton_{$button_id}' type='submit' name='submit' value='{$button['text']}' class='button medium'/>";
    }

    /**
     * Render a field with it's table rows.
     * Will render input field, display field, or note depending on field and page action.
     *
     * @param array $field
     *   The field settings.
     * @param array $row
     *   The table data for this entry.
     *
     * @return string
     *   Rendered HTML starting with <tr> tag.
     */
    protected function renderFormRow(&$field, $row) {
        $output = '';
        if ($which_field = $this->whichField($field)) {
            // double column width row
            if ($field['type'] == 'note') {
                $output = '<tr><td colspan="2"><h3>';
                $output .= !empty($field['note']) ? $field['note'] : $field['display_name'];
                $output .= '</h3></td></tr>';
            } elseif (!empty($field['width']) && $field['width'] == "full") {
                $output .= '<tr><td colspan="2">' . $field['display_name'] . '</td></tr>';
                $output .= '<tr><td colspan="2">';
                $output .= $this->renderFieldInputOrValue($which_field, $field, $row);
                if ($field['default_reset']) {
                    $output .= '<input type="button" value="Reset to default" onclick="lightning.table.resetField(\'' . $field['field'] . '\');" />';
                }
                $output .= '</td></tr>';
            } else {
                $output .= '<tr><td valign="top">';
                $output .= $field['display_name'];
                $output .= '</td><td valign="top">';
                $output .= $this->renderFieldInputOrValue($which_field, $field, $row);
                $output .= '</td></tr>';
            }
        }
        return $output;
    }

    protected function renderFieldInputOrValue($which_field, $field, $row) {
        $output = '';
        if ($which_field == 'display') {
            $output = $this->printFieldValue($field, $row);
        } elseif ($which_field == 'edit') {
            $output = $this->renderEditField($field, $row);
            if (!empty($field['note'])) {
                $output .= $field['note'];
            }
        }
        if (!empty($field['default_reset'])) {
            $output .= "<input type='button' value='Reset to default' onclick='lightning.table.resetField(\"{$field['field']}\");' />";
        }
        return $output;
    }

    /**
     * Render a field by name for templates.
     *
     * @param string $field_name
     *
     * @return string
     */
    public function renderField($field_name) {
        $field = $this->fields[$field_name];
        $which_field = $this->whichField($field);
        $row = !empty($this->id) ? $this->list : $this->list[$this->currentRow];
        return $this->renderFieldInputOrValue($which_field, $field, $row);
    }

    // THIS IS CALLED TO RENDER LINKED TABLES IN view/edit/new MODE
    // (full form)
    protected function render_form_linked_tables() {
        $output = '';
        foreach ($this->links as $link => &$link_settings) {
            if (empty($link_settings['table'])) {
                $link_settings['table'] = $link;
            }

            // DISPLAY NAME ON THE LEFT
            $output .= '<tr><td>' . $this->getDisplayName($link_settings, $link) . '</td><td>';

            // LOAD THE LINKED ROWS
            // The local key is the primary key column by default or another specified column.
            $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->getKey();
            // The value of the local key column.
            $local_id = ($this->table) ? $this->list[$local_key] : $this->id;

            // If there is a local key ID and no active list, load it.
            // active_list is the list of attached items.
            if ($local_id > 0 && !isset($link_settings['active_list'])) {
                $link_settings['active_list'] = $this->load_all_active_list($link_settings, $local_id);
            } elseif (empty($local_id)) {
                // If there is no local ID, this is probably a new item, so the list should be blank.
                $link_settings['active_list'] = [];
            }

            $link_settings['row_count'] = count($link_settings['active_list']);

            // IN EDIT/NEW MODE, SHOW A FULL FORM
            if ($this->action == 'edit' || $this->action == 'new') {
                // IN EDIT MODE WITH THE full_form OPTION, SHOW THE FORM WITH ADD/REMOVE LINKS
                if (!empty($link_settings['full_form'])) {
                    // editable forms (1 to many)
                    $output .= $this->render_full_linked_table_editable($link, $link_settings);
                } else {
                    // drop down menu (many to many)
                    if (empty($link_settings['type'])) {
                        $link_settings['type'] = 'select';
                    }
                    switch ($link_settings['type']) {
                        case 'image':
                            $output .= $this->renderLinkedTableEditableImage($link, $link_settings);
                            break;
                        case 'autocomplete':
                            $output .= $this->renderLinkedTableEditableAutocomplete($link, $link_settings);
                            break;
                        default;
                            $output .= $this->renderLinkedTableEditableSelect($link, $link_settings);
                            break;
                    }
                }
            }

            // FULL FORM MODE INDICATES THAT THE LINKED TABLE IS A SUB TABLE OF THE MAIN TABLE - A 1(table) TO MANY (subtable) RELATIONSHIP
            // for view mode, if "display" is set, use the "display" template
            elseif ($this->action == 'view') {
                if (isset($link_settings['display'])) {
                    // IN VIEW MODE WITH THE full_form OPTION, JUST SHOW ALL THE DATA
                    // loop for each entry
                    foreach ($link_settings['active_list'] as $l) {
                        // loop for each field
                        $display = $link_settings['display'];
                        foreach ($l as $f => $v) {
                            if (isset($link_settings['fields'][$f])) {
                                if ($link_settings['fields'][$f]['field'] == '') $link_settings['fields'][$f]['field'] = $f;
                                $display = str_replace('{' . $f . '}', $this->printFieldValue($link_settings['fields'][$f], $l), $display);
                            }
                        }
                        $output .= $display;
                        $output .= $link_settings['seperator'];
                        // insert break here?
                    }
                    // THIS IS A MANY TO MANY RELATIONSHIP
                    // otherwise just list out all the fields
                } elseif (!empty($link_settings['full_form']) && $link_settings['full_form'] === true) {
                    // full form view
                    foreach ($link_settings['active_list'] as $l) {
                        $output .= "<div class='subtable'><table>";
                        // SHOW FORM FIELDS
                        foreach ($link_settings['fields'] as $f => &$s) {
                            $s['field'] = $f;
                            $s['form_field'] = "st_{$link}_{$f}_{$l[$link_settings['key']]}";
                            if ($this->whichField($s) == "display") {
                                $output .= "<tr><td>{$s['display_name']}</td><td>";
                                $output .= $this->printFieldValue($s, $l);
                            }
                        }
                        // ADD REMOVE LINKS
                        $output .= "</table></div>";
                    }
                }
                // LIST MODE
            }

            $output .= '</td></tr>';
        }

        return $output;
    }



    // this renders all the linked items as full forms so they can be edited and new items can be added
    // this would imply to show only the links that are actively linked to this table item for editing
    // this is a 1 to many relationship. it will load all of the links made using load_all_active_list()
    // any link connected is "owned" by this table row and will be editable from this table in edit mode
    protected function render_full_linked_table_editable($link_id, &$link_settings) {
        $output = "<input type='hidden' name='delete_subtable_{$link_id}' id='delete_subtable_{$link_id}' />";
        $output .= "<input type='hidden' name='new_subtable_{$link_id}' id='new_subtable_{$link_id}' />";
        if (count($link_settings['active_list']) > 0)
            foreach ($link_settings['active_list'] as $l) {
                $output .= "<div class='subtable' id='subtable_{$link_id}_{$l[$link_settings['key']]}'><table>";
                // SHOW FORM FIELDS
                foreach ($link_settings['fields'] as $f => &$s) {
                    $link_settings['fields'][$f]['field'] = $f;
                    $link_settings['fields'][$f]['form_field'] = "st_{$link_id}_{$f}_{$l[$link_settings['key']]}";
                    $output .= $this->renderFormRow($s, $l);
                }
                // ADD REMOVE LINKS
                $output .= "</table>";
                $output .= "<span class='link' onclick='delete_subtable(this)'>{$link_settings['delete_name']}</span>";
                $output .= "</div>";
            }

        // ADD BLANK FORM FOR ADDING NEW LINK
        $output .= "<div class='subtable' id='subtable_{$link_id}__N_' style='display:none;'><table>";

        // SHOW FORM FIELDS
        foreach ($link_settings['fields'] as $f => &$s) {
            $link_settings['fields'][$f]['field'] = $f;
            $link_settings['fields'][$f]['form_field'] = "st_{$link_id}_{$f}__N_";
            $output .= $this->renderFormRow($s, []);
        }

        // ADD REMOVE LINKS
        $output .= "</table>";
        $output .= "<span class='link' onclick='delete_subtable(this)'>{$link_settings['delete_name']}</span>";
        $output .= "</div>";

        // ADD NEW LINK
        $output .= "<span class='link' onclick='new_subtable(\"{$link_id}\")'>{$link_settings['add_name']}</span>";
        return $output;
    }

    /**
     * Renders an 'upload image' button and a list of selected current images.
     *
     * @param $link_settings
     *
     * @return string
     */
    protected function renderLinkedTableEditableImage($link_id, &$link_settings) {
        HTMLEditor::init(true);
        JS::startup('lightning.table.init()');
        // TODO: This doesn't return anything valuable. Is it used anywhere?
        $link_settings['web_location'] = $this->getImageLocationWeb($link_settings, '');
        JS::set('table.links.' . $link_id, $link_settings);
        $output = '<span class="button medium add_image" id="add_image_' . $link_id . '">Add Image</span>';
        $output .= '<span class="linked_images" id="linked_images_' . $link_id . '">';
        $link_settings['conform_name'] = false;
        foreach ($link_settings['active_list'] as $image) {
            $output .= '<span class="selected_image_container">
                <input type="hidden" name="linked_images_' . $link_id . '[]" value="' . $image['image'] . '">
                <span class="remove fa fa-close"></span>
                <img src="' . $this->getImageLocationWeb($link_settings, $image['image']) . '"></span>';
        }
        $output .= '</span>';

        return $output;
    }

    protected function renderLinkedTableEditableAutocomplete($link_id, &$link_settings) {

        if (!empty($link_settings['create'])) {
            JS::set('table_data.links.' . $link_id . '.create', true);
            JS::addSessionToken();
        }

        // Create a search box.
        $output = BasicHTML::text($link_id . '_autocomplete', '', [
            'class' => 'autocomplete_link',
            'data-name' => $link_id,
            'data-type' => 'link',
        ]);

        $output .= $this->renderLinkedTableEditableSelectedBoxes($link_id, $link_settings);

        return $output;
    }

    /**
     * this renders a linked table showing a list of all available options, and a list of
     * all items that are already added to this table item
     * this is a many to many - where you can add any of the options from loadAllLinkOptions()
     * but you can't edit the actual content unless you go to the table page for that table
     *
     * @param $link_id
     * @param $link_settings
     * @return string
     */
    protected function renderLinkedTableEditableSelect($link_id, &$link_settings) {
        // show list of options to ad
        // IN REGULAR MODE IF edit_js? IS TURNED ON
        $output = '';
        if (!empty($link_settings['edit_js'])) {
            $output .= "<select name='{$link_id}_list' id='{$link_id}_list' ></select>";
            $output .= "<input type='button' name='add_{$link_id}_button' value='Add {$link_id}' id='add_{$link_id}_button' onclick='{$link_settings['edit_js']}.newLink(\"{$this->id}\")' />";

            //DEFAULT VIEW MODE
        } else {
            $this->loadAllLinkOptions($link_settings);
            $options = [''];
            foreach ($link_settings['options'] as $l) {
                $key = !empty($link_settings['index_fkey']) ? $link_settings['index_fkey'] : $link_settings['key'];
                $options[$l[$key]] = $l[$link_settings['display_column']];
            }
            $output .= BasicHTML::select($link_id . '_list', $options);
            $output .= "<input type='button' name='add_{$link_id}_button' value='Add {$link_id}' class='add-link' id='add_{$link_id}_button' data-link='{$link_id}' />";
        }

        if (!empty($link_settings['pop_add'])) {
            $location = !empty($link_settings['table_url']) ? $link_settings['table_url'] : "/table?table=" . $link_id;
            $output .= "<a onclick='lightning.table.newPop(\"{$location}\",\"{$link_id}\",\"{$link_settings['display_column']}\")'>Add New Item</a>";
        }

        $output .= $this->renderLinkedTableEditableSelectedBoxes($link_id, $link_settings);

        return $output;
    }

    protected function renderLinkedTableEditableSelectedBoxes($link_id, &$link_settings) {
        // Create the hidden array field.
        $value = implode(',', PHP::getArrayPropertyValues($link_settings['active_list'], $link_settings['key'])) . ',';
        $output = BasicHTML::hidden($link_id . '_input_array', $value);

        $output .= "<br /><div id='{$link_id}_list_container'>";
        // create each item as a viewable deleteable box
        foreach ($link_settings['active_list'] as $init) {
            $output .= "<div class='{$link_id}_box table_link_box_selected' id='{$link_id}_box_{$init[$link_settings['key']]}'>{$init[$link_settings['display_column']]}
						<i class='remove-link fa fa-close' data-link='{$link_id}' data-link-item='{$init[$link_settings['key']]}' ></i></div>";
        }
        $output .= '</div>';
        return $output;
    }

    // this loads all links that are actively joined by a foreign key on the remote table
    // or by a link table in between. this is used for a one to many relationship, (1 table row to many links)
    protected function load_all_active_list(&$link_settings, $row_id) {
        $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->getKey();
        if (!empty($link_settings['index'])) {
            // many to many - there will be an index table linking the two tables together
            $table = $link_settings['index'];
            $join = [];
            if (!empty($link_settings['index_fkey'])) {
                $join[] = [
                    'join' => $link_settings['table'],
                    'on' =>  [$link_settings['index'] . '.' . $link_settings['key']
                        => ['expression' => $link_settings['table'] . '.' . $link_settings['index_fkey']]]
                ];
            } else {
                $join[] = [
                    'join' => $link_settings['table'],
                    'using' => $link_settings['key'],
                ];
            }
            $where = [$link_settings['index'] . '.' . $local_key => $row_id];
            if (!empty($link_settings['accessControl'])) {
                $where += $link_settings['accessControl'];
            }
            return $this->database->selectAllQuery([
                'from' => $table,
                'join' => $join,
                'where' => $where,
                'order_by' => [$link_settings['display_column'] => 'ASC'],
            ]);
        } else {
            // @TODO: remove this, it should be a 'lookup' type instead.
            // 1 to many - each remote table will have a column linking it back to this table
            return $this->database->selectAll($link_settings['table'], [$local_key => $row_id]);
        }
    }

    /**
     * Load all possible options for a linked table (Many to many).
     *
     * @param array $link_settings
     */
    protected function loadAllLinkOptions(&$link_settings) {
        $where = !empty($link_settings['accessControl']) ? $link_settings['accessControl'] : [];
        $link_settings['options'] = $this->database->selectAll($link_settings['table'], $where, [], 'ORDER BY ' . $link_settings['display_column']);
    }

    /**
     * Render the table pagination links.
     *
     * @return string
     *   The rendered HTML output.
     */
    protected function getPagination() {
        $params = $this->getUrlParameters($this->action);
        unset($params['page']);
        return new Pagination([
            'rows' => $this->listCount,
            'rows_per_page' => $this->maxPerPage,
            'base_path' => $this->action_file,
            'parameters' => $params,
        ]);
    }

    /**
     * Replace variables in a string with values from the current selected row.
     *
     * @param string $string
     *   The original string containing replacement variables in curly brackets
     *
     * @return string
     *   The updated string with variables replaced.
     */
    protected function replaceURLVariables($string) {
        if (!empty($this->id)) {
            foreach ($this->list as $key => $value) {
                $string = str_replace('{' . $key . '}', $value, $string);
            }
        }
        return $string;
    }

    /**
     * Get all of the request parameters for forwarding links.
     *
     * @param string $action
     * @param int $id
     * @param string $field
     * @param array $other
     * @return array
     */
    public function getUrlParameters($action = '', $id = 0, $field = '', $other = []) {
        $vars = [];
        if ($action == 'list') {
            $vars['page'] = $id;
        } elseif (!empty($id)) {
            $vars['id'] = $id;
        } elseif (!empty($this->id) > 0) {
            $vars['id'] = $this->id;
        }
        if ($action != '') $vars['action'] = $action;
        if ($this->table_url) $vars['table'] = $this->table;
        if (isset($this->parentLink)) $vars[$this->parentLink] = $this->parentId;
        if ($field != '') $vars['f'] = $field;
        if ($this->cur_subset && $this->cur_subset != $this->subset_default) $vars['ss'] = $this->cur_subset;
        if (!empty($this->additional_action_vars)) {
            $vars = array_merge($this->additional_action_vars, $vars);
        }
        if (!empty($other)) {
            $vars = array_merge($vars, $other);
        }

        // Conform the sorting variable.
        if (!empty($vars['sort']) && is_array($vars['sort'])) {
            $sort_strings = [];
            foreach ($vars['sort'] as $f => $d) {
                $direction = $d;
                switch ($d) {
                    case 'ASC':
                        $direction = 'A';
                        break;
                    case 'DESC':
                        $direction = 'D';
                        break;
                    case 'X':
                        $direction =
                            (!empty($this->sort[$f]) && $this->sort[$f] == 'ASC')
                                ? 'D' : 'A';
                        break;
                }
                $sort_strings[] = $f . ':' . $direction;
            }
            $vars['sort'] = implode(';', $sort_strings);
        }

        return $vars;
    }

    public function createUrl($action = '', $id = 0, $field = '', $other = []) {
        $parameters = $this->getUrlParameters($action, $id, $field, $other);
        return $this->action_file . (!empty($parameters) ? ('?' . http_build_query($parameters)) : '');
    }

    public function renderTemplate($template) {
        // Use the global template so any set variables are already set.
        return Template::getInstance()->render($template, true);
    }

    public function template_item_vars($template, $id) {
        $template = str_replace('{table_link_edit}', $this->createUrl("edit", $id), $template);
        $template = str_replace('{table_link_delete}', $this->createUrl("delete", $id), $template);
        $template = str_replace('{table_link_view}', $this->createUrl("view", $id), $template);
        $template = str_replace('{key}', $id, $template);

        // look for linked file names
        preg_match_all('/\{table_link_file\.([a-z0-9_]+)\}/i', $template, $matches);
        for ($i = 1; $i < count($matches); $i = $i + 2) {
            $m = $matches[$i][0];
            if ($this->fields[$m]['type'] == 'file') {
                $template = str_replace('{table_link_file.' . $m . '}', $this->createUrl("file", $id, $m), $template);
            }
        }

        return $template;
    }

    protected function loadMainFields() {
        if (empty($this->fields)) {
            $this->fields = $this->get_fields($this->table, $this->preset);
        }
    }

    /**
     * Get the list of fields.
     *
     * @param string $table
     *   The table to load field data from.
     * @param array $preset
     *   The extra field data settings.
     *
     * @return array
     *   The processed field data.
     */
    protected function get_fields($table, $preset) {
        if (!empty($table)) {
            $fields = $this->database->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(Database::FETCH_ASSOC);
        } else {
            $fields = [];
        }

        $return_fields = [];
        foreach ($fields as $column => $field) {
            $column = !empty($field['Field']) ? $field['Field'] : $column;
            $return_fields[$column] = [];
            foreach ($field as $key => $value) {
                $return_fields[$column][strtolower($key)] = $value;
            }
        }

        // Normalize string presets as type.
        foreach ($preset as &$p) {
            if (is_string($p)) {
                $p = ['type' => $p];
            }
        }

        $return_fields = array_replace_recursive($return_fields, $preset);
        //make sure there is a 'field' element and 'display_name' for each $field
        foreach ($return_fields as $f => &$field) {
            if (empty($field['display_name'])) {
                $field['display_name'] = ucwords(str_replace("_", " ", $f));
            }
            if (!isset($field['field'])) {
                $field['field'] = $f;
            }
            if (empty($field['type'])) {
                $field['type'] = 'string';
            }
            if ($field['type'] == "file") {
                if (isset($field['extension'])) {
                    $return_fields[$field['extension']]['type'] = "hidden";
                }
            }
        }

        return $return_fields;
    }

    /**
     * Determine if the field should be an 'edit' field or 'display' field.
     *
     * @param array $field
     *   The field settings array.
     *
     * @return string|boolean
     *   The render type. False if it should not be shown.
     */
    protected function whichField(&$field) {
        switch ($this->action) {
            case 'new':
            case 'duplicate':
                if ($this->userInputNew($field)) {
                    return 'edit';
                } elseif ($this->userDisplayNew($field)) {
                    return 'display';
                } else {
                    return false;
                }
                break;
            case 'edit':
                if ($this->userInputEdit($field)) {
                    return 'edit';
                } elseif ($this->userDisplayEdit($field)) {
                    return 'display';
                } else {
                    return false;
                }
                break;
            case 'view':
                return $this->displayView($field) ? 'display' : false;
                break;
            case 'export':
                if (!in_array($field['type'], ['hidden', 'note'])) {
                    return false;
                }
            case 'list':
            default:
                return $this->displayList($field) ? 'display' : false;
                break;
        }
    }

    // is the field editable in these forms
    protected function userInputNew(&$field) {
        if (isset($field['render_' . $this->action . '_field'])) {
            return true;
        }
        if ($field['type'] == 'note') {
            return true;
        }
        if ($field['type'] == 'hidden' || (!empty($field['hidden']) && $field['hidden'] == 'true')) {
            return false;
        }
        if ($field['field'] == $this->getKey() && empty($field['editable'])) {
            return false;
        }
        if ($field['field'] == $this->parentLink) {
            return false;
        }
        if (isset($field['insertable']) && $field['insertable'] == false) {
            return false;
        }
        if (isset($field['editable']) && $field['editable'] === false) {
            return false;
        }
        if (!empty($field['list_only'])) {
            return false;
        }
        return true;
    }

    protected function userInputEdit(&$field) {
        if (isset($field['render_' . $this->action . '_field'])) {
            return true;
        }
        if ($field['type'] == "note") {
            return true;
        }
        if ($field['field'] == $this->getKey()) {
            return false;
        }
        if ($field['field'] == $this->parentLink) {
            return false;
        }
        if (isset($field['editable']) && $field['editable'] === false) {
            return false;
        }
        if (!empty($field['list_only'])) {
            return false;
        }
        if (!empty($field['set_on_new'])) {
            return false;
        }
        return true;
    }

    protected function userDisplayNew(&$field) {
        if (!empty($field['list_only'])) {
            return false;
        }
        if (isset($field['insertable']) && $field['insertable'] == false) {
            return false;
        }
        // TODO: This should be replaced by an overriding method in the child class.
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['display_new_value']) && is_callable($field['display_new_value']))
        ) {
            return true;
        }
        if ($field['field'] == $this->parentLink) {
            return false;
        }
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden'])) {
            return false;
        }
        return true;
    }

    protected function userDisplayEdit(&$field) {
        if (!empty($field['list_only'])) {
            return false;
        }
        // TODO: This should be replaced by an overriding method in the child class.
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['display_edit_value']) && is_callable($field['display_edit_value']))
        ) {
            return true;
        }
        if ($field['field'] == $this->parentLink) {
            return false;
        }
        return true;
    }

    protected function displayList(&$field) {
        // TODO: This should be replaced by an overriding method in the child class.
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['displayList_value']) && is_callable($field['displayList_value']))
        ) {
            return true;
        }
        if ($field['field'] == $this->parentLink) {
            return false;
        }
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden'])) {
            return false;
        }
        if (!empty($field['unlisted'])) {
            return false;
        }
        return true;
    }

    /**
     * Check if we should display this field in view mode.
     *
     * @param array $field
     *
     * @return boolean
     */
    protected function displayView(&$field) {
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['displayView_value']) && is_callable($field['displayView_value']))
        ) {
            return true;
        }
        if ($field['type'] == "note" && $field['view']) {
            return true;
        }
        if ($field['field'] == $this->parentLink) {
            return false;
        }
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden'])) {
            return false;
        }
        if (!empty($field['list_only'])) {
            return false;
        }
        return true;
    }

    /**
     * Check if we should insert a value on this field.
     *
     * @param array $field
     *
     * @return boolean
     */
    protected function setValueOnNew(&$field) {
        if (
            isset($field['value'])
            || isset($field['insert_function'])
            || isset($field['submit_function'])
            || !empty($field['force_default_new']) || !empty($field['default'])
            || !empty($field['set_on_new'])
        ) {
            return true;
        }
        if (
            ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            || (isset($field['editable']) && $field['editable'] === false)
            || (isset($field['insertable']) && $field['insertable'] === false)
            || !empty($field['list_only'])
            || (!empty($this->fieldOrder) && !in_array($field['field'], $this->fieldOrder))
        ) {
            return false;
        }

        // Default.
        return true;
    }

    /**
     * Check if we should update the value on this field.
     *
     * @param array $field
     *
     * @return boolean
     */
    protected function setValueOnUpdate(&$field) {
        if (
            isset($field['value'])
            || isset($field['modified_function'])
            || isset($field['submit_function'])
        ) {
            return true;
        }

        if (
            ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            || $field['field'] == $this->parentLink
            || (isset($field['editable']) && $field['editable'] === false)
            || !empty($field['list_only'])
            || $field['field'] == $this->getKey()
            || (!empty($this->fieldOrder) && !in_array($field['field'], $this->fieldOrder))
        ) {
            return false;
        }

        // Default.
        return true;
    }

    protected function updateAccessTable() {
        if (isset($this->accessTable)) {
            $accessTableValues = $this->getFieldValues($this->fields, true);
            if (!empty($accessTableValues)) {
                $this->database->update($this->accessTable, $accessTableValues, array_merge($this->accessTableWhere, [$this->getKey() => $this->id]));
            }
        }
    }

    protected function getFieldValues(&$field_list, $accessTable = false) {
        $output = [];
        $dependenciesMet = true;
        foreach ($field_list as $f => $field) {
            // check for settings that override user input
            if ($this->action == 'insert' && !$this->setValueOnNew($field)) {
                continue;
            } elseif ($this->action == 'update' && !$this->setValueOnUpdate($field)) {
                continue;
            }
            if ($field['type'] == 'note') {
                continue;
            }
            if (!empty($field['nocolumn'])) {
                continue;
            }

            if (!empty($field['table']) && $field['table'] == 'access' && !$accessTable) {
                continue;
            } elseif (!isset($field['table']) && $accessTable) {
                continue;
            }

            unset($val);
            $sanitize = false;
            $html = false;
            $ignore = false;

            if (!isset($field['form_field'])) {
                $field['form_field'] = $field['field'];
            }
            // GET THE FIELD VALUE

            // OVERRIDES

            if (!empty($field['value'])) {
                // Fixed value.
                $val = $field['value'];
            } elseif (!empty($field['force_default_new']) && $this->action == 'insert') {
                // Fixed default value.
                $val = $field['default'];
            } elseif ($this->parentLink == $field['field']) {
                // If the field is a parent link.
                $val = $this->parentId;
            } elseif ($this->action == 'insert' && isset($field['insert_function'])) {
                // function when modified
                $this->preset[$field['field']]['insert_function']($output);
                continue;
            } elseif ($this->action == 'update' && isset($field['modified_function'])) {
                $this->preset[$field['field']]['modified_function']($output);
                continue;
            } elseif (isset($field['submit_function'])) {
                // covers both insert_function and modified_function
                $this->preset[$field['field']]['submit_function']($output);
                continue;
            } else {
                switch (preg_replace('/\([0-9]+\)/', '', $field['type'])) {
                    case 'image':
                    case 'file':
                        if (!empty($field['browser'])) {
                            $val = $this->getStorageName($field, Request::get($field['field']));
                        } else {
                            if ($_FILES[$field['field']]['size'] > 0
                                && $_FILES[$field['field']]['error'] == UPLOAD_ERR_OK
                                && (
                                    (
                                        (!isset($field['replaceable']) || $field['replaceable'] === false)
                                        && $this->action == 'update'
                                    )
                                    || $this->action == 'insert'
                                )
                            ) {
                                if ($field['type'] == 'file') {
                                    $val = $this->saveFile($field, $_FILES[$field['field']]);
                                } else {
                                    $val = $this->saveImage($field, $_FILES[$field['field']]);
                                }
                            } else {
                                $ignore = true;
                            }
                        }
                        break;
                    case 'date':
                        $val = Time::getDate($field['form_field'], !empty($field['allow_blank']));
                        break;
                    case 'time':
                        $val = Time::getTime($field['form_field'], !empty($field['allow_blank']), !empty($field['timezone']) ? $field['timezone'] : null);
                        break;
                    case 'datetime':
                        $val = Time::getDateTime($field['form_field'], !empty($field['allow_blank']), !empty($field['timezone']) ? $field['timezone'] : null);
                        break;
                    case 'checkbox':
                        $val = Request::get($field['form_field'], Request::TYPE_BOOLEAN_INT);
                        break;
                    case 'checklist':
                        $vals = '';
                        $maxi = 0;
                        foreach ($field['options'] as $i => $opt) {
                            if (is_array($opt)) {
                                $maxi = max($maxi, $opt[0]);
                            } else {
                                $maxi = max($maxi, $i);
                            }
                        }
                        for ($i = 0; $i <= $maxi; $i++) {
                            $vals .= ($_POST[$field['form_field'] . '_' . $i] == 1 || $_POST[$field['form_field'] . '_' . $i] == "on") ? 1 : 0;
                        }
                        $val = bindec(strrev($vals));
                        break;
                    case 'bit':
                        $val = ['bit' => decbin(Request::get($field['form_field'], Request::TYPE_INT))];
                        break;
                    case 'text':
                    case 'mediumtext':
                    case 'longtext':
                    case 'div':
                    case 'html':
                        if ($this->trusted || !empty($field['trusted'])) {
                            $val = Request::get($field['form_field'], Request::TYPE_TRUSTED_HTML);
                        } else {
                            $val = Request::get($field['form_field'],
                                Request::TYPE_HTML,
                                !empty($field['allowed_html']) ? $field['allowed_html'] : '',
                                !empty($field['allowed_css']) ? $field['allowed_css'] : '',
                                !empty($field['trusted']) || $this->trusted,
                                !empty($field['full_page'])
                            );
                        }
                        break;
                    case 'json':
                        $val = Request::post($field['form_field'], Request::TYPE_JSON_STRING);
                        break;
                    case 'int':
                    case 'float':
                    case 'email':
                    case 'url':
                    default:
                        $val = Request::post($field['form_field'], $field['type']);
                        break;
                }
            }

            // If there is an alternate default value
            if (!isset($val) && $this->action == "insert" && isset($field['default'])) {
                $val = $field['default'];
                // Developer input - could require sanitization.
                $sanitize = true;
            }

            // Sanitize the input.
            $sanitize_field = $this->action == 'insert' ? 'insert_sanitize' : 'modify_sanitize';
            if (
                $sanitize &&
                ((!isset($field[$sanitize_field]) || $field[$sanitize_field] !== false)
                    || (!isset($field['sanitize']) || $field['sanitize'] !== false))
            ) {
                $val = $this->sanitizeInput($val, $html);
            }

            // If this value is required.
            // This is allowed to be empty if it's an encrypted field and there is already and entry with a value.
            if (!empty($field['required']) && empty($val) && empty($field['encrypted']) && empty($this->list[$f])) {
                Messenger::error('The field ' . $this->fields[$f]['display_name'] . ' is required.');
                $dependenciesMet = false;
            }

            // If the value needs to be encrypted
            if (!empty($field['encrypted'])) {
                $val = $this->encrypt($this->table, $field['field'], $val);
            }

            if (!$ignore && empty($field['no_save'])) {
                $output[$field['field']] = $val;
            }
        }

        $dependenciesMet &= $this->processFieldValues($output);

        return $dependenciesMet ? $output : false;
    }

    protected function processFieldValues(&$values) {
        return true;
    }

    protected function getStorageName($field, $web_url) {
        $fileHandler = $this->getFileHandler($field);
        return $fileHandler->getFileFromWebURL($web_url);
    }

    protected function saveFile($field, $file) {

        // Delete previous file.
        $fileHandler = $this->getFileHandler($field);
        $new_file = $this->getFullFileName($file['name'], $field, null);
        if ($this->id && !empty($field['replace'])) {
            $this->getRow();
            if ($fileHandler->exists($new_file)) {
                $fileHandler->delete($new_file);
            }
        } else {
            $i = 1;
            $filename = preg_replace('/:/', '', $file['name']);
            $filename = preg_replace('~\.(?!.*\.)~', ':', $filename);
            $components = explode(':', $filename);
            if (count($components) == 1) {
                $prefix = $components[0];
                $suffix = '';
            } else {
                $prefix = $components[0];
                $suffix = '.' . $components[1];
            }
            while ($fileHandler->exists($new_file)) {
                $new_file = $this->getFullFileName($prefix . '_' . $i . $suffix, $field, null);
            }
        }

        // Write the file.
        $fileHandler->moveUploadedFile($new_file, $file['tmp_name']);

        return $new_file;
    }

    /**
     * Save the uploaded data as modified images.
     *
     * @param array $field
     *   The field settings.
     * @param array $file
     *   The uploaded file information from $_FILES
     *
     * @return string
     *   The name of the last image created.
     */
    public function saveImage($field, $file) {
        // Load the image
        if (!is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        $imageObj = Image::loadFromPost($field['field']);

        if (!$imageObj) {
            return false;
        }

        if (empty($field['images'])) {
            $field['images'] = [$field];
        }

        $images = $this->getCompositeImageArray($field);
        $filename_base = $this->getNewImageFilenameBase($images, $file);
        $this->createImages($filename_base, $field, $images, $imageObj, $file);
        return $filename_base;
    }

    protected function getCompositeImageArray($field) {
        $images = [];
        if (!empty($field['images'])) {
            foreach ($field['images'] as $image) {
                $image = array_replace($field, $image);
                $images[] = $image;
            }
            return $images;
        } else {
            return [$field];
        }
    }

    /**
     * @param string $filename_base
     * @param array $field
     * @param array $images
     * @param Image $imageObj
     * @param array $file
     *   The uploaded file array if available.
     * @return bool
     */
    public function createImages($filename_base, $field, $images, $imageObj, $file = null, $replace_existing = true) {
        $fileHandler = $this->getFileHandler($field);
        foreach ($images as $image) {
            // If the image is never modified, we can copy it as if it's the original.
            $modified = false;

            // Get the output file location.
            $output_format = $this->getOutputFormat($image, $file);
            $new_image = $this->getFullFileName($filename_base, $image, $file);

            // If we are not going to replace existing and this already exists, continue to the next.
            if (!$replace_existing && $fileHandler->exists($new_image)) {
                continue;
            }

            if (!empty($image['original'])) {
                if (!empty($file)) {
                    // The file was just uploaded, so make sure to move it to the correct location.
                    $fileHandler->moveUploadedFile($new_image, $file['tmp_name']);
                }
                // If the 'original' does not exist, we still need to create it.
                if ($fileHandler->exists($new_image)) {
                    continue;
                }
            }

            if (!empty($image['image_preprocess']) && is_callable($image['image_preprocess'])) {
                $imageObj->source = $image['image_preprocess']($imageObj->source);
                $modified = true;
            }

            if (!$imageObj->source) {
                // The image failed to load.
                return false;
            }

            // Set the quality.
            $quality = !empty($image['quality']) ? $image['quality'] : 75;

            if ($imageObj->process($image)) {
                $modified = true;
            }

            if (!empty($image['image_postprocess']) && is_callable($image['image_postprocess'])) {
                $imageObj->processed = $image['image_postprocess']($imageObj->processed);
                $modified = true;
            }

            if (!$modified && !empty($file) && $this->getUploadedFileFormat($file) == $output_format && !empty($field['keep_unprocessed'])) {
                // The file was just uploaded, not modified, and destined for the same format. Just upload it.
                $fileHandler->moveUploadedFile($new_image, $file['tmp_name']);
            }
            else {
                switch ($output_format) {
                    case 'png':
                        $fileHandler->write($new_image, $imageObj->getPNGData());
                        break;
                    case 'jpg':
                    default:
                        $fileHandler->write($new_image, $imageObj->getJPGData($quality));
                        break;
                }
            }
        }
    }

    /**
     * Upload all the images in the table.
     */
    public function updateImages($missing_only = false, $id = null) {
        if ($id) {
            $this->id = $id;
            $this->getRow();
            $this->list = [$this->list];
        } else {
            $this->loadFullListCursor();
        }

        // Figure out which fields to apply.
        $this->loadMainFields();
        $image_fields = [];
        foreach ($this->fields as $f => $field) {
            if (!empty($field['type']) && $field['type'] == 'image') {
                $image_fields[$f] = $field;
            }
        }

        // There are no image fields.
        if (empty($image_fields)) {
            return;
        }

        // Update the images.
        foreach ($this->list as $entry) {
            foreach ($image_fields as $f => $field) {
                $fileHandler = $this->getFileHandler($field);
                $images = $this->getCompositeImageArray($field);

                if (!empty($entry[$f])) {
                    $source_image_data = null;
                    foreach ($images as $image) {
                        if (!empty($image['original'])) {
                            $source_image_data = $image;
                            break;
                        }
                    }

                    // If there is no defined original image.
                    if (empty($source_image_data)) {
                        // Try the first image.
                        $source_image_data = $images[0];
                    }

                    // Check if the file exists.
                    $source_image = $this->getFullFileName($entry[$f], $source_image_data);
                    if (!$fileHandler->exists($source_image)) {
                        $found = false;
                        // If not, try another one.
                        foreach ($images as $image) {
                            $source_image = $this->getFullFileName($entry[$f], $image);
                            if ($fileHandler->exists($source_image)) {
                                $found = true;
                                break;
                            }
                        }
                        // If there still isn't an image, continue to the next row.
                        if (!$found) {
                            continue;
                        }
                    }

                    $image_resource = Image::createFromString($fileHandler->read($source_image));
                    if ($image_resource) {
                        $this->createImages($entry[$f], $field, $images, $image_resource, [], !$missing_only);
                    }
                }
            }
        }
    }

    protected function getNewImageFilenameBase($images, $uploaded_file) {
        do {
            $random_file = rand(0, 999999);
            $files_exist = false;
            foreach ($images as $image) {
                $handler = $this->getFileHandler($image);
                $file = $this->getFullFileName($random_file, $image, $uploaded_file);
                if ($handler->exists($file)) {
                    $files_exist = true;
                    break;
                }
            }
        } while ($files_exist);
        return $random_file;
    }

    /**
     * @param string $file_name
     * @param array $field
     * @param array $uploaded_file
     *   The uploaded file data from $_FILES.
     *   Only pass this if you want to auto detect the file extension.
     *
     * @return string
     */
    protected function getFullFileName($file_name, $field, $uploaded_file = null) {
        // Add developer specified prefix/suffix
        if (!empty($field['file_prefix'])) {
            $file_name = $field['file_prefix'] . $file_name;
        }
        if (!empty($field['file_suffix'])) {
            $file_name .= $field['file_suffix'];
        }
        // Add the format suffix.
        if ($uploaded_file) {
            $file_name .= '.' . $this->getOutputFormat($field, $uploaded_file);
        }

        return $file_name;
    }

    /**
     * @param $field
     *
     * @return \Lightning\Tools\IO\FileHandlerInterface
     */
    protected function getFileHandler($field) {
        // TODO: $field['location'] is deprecated. All tables should be updated to use container instead.
        if (empty($field['container']) &!empty($field['location'])) {
            $field['container'] = [
                'storage' => $field['location'],
                'url' => null . '/',
            ];
        }
        $handler = empty($field['file_handler']) ? '' : $field['file_handler'];
        return FileManager::getFileHandler($handler, $field['container']);
    }

    protected function decode_bool_group($int) {
        return str_split(strrev(decbin($int)));
    }

    // get the int val of a specific bit - ie convert 1 (2nd col form right or 10) to 2
    // this way you can search for the 2nd bit column in a checklist with: "... AND col&".table::get_bit_int(2)." > 0"
    public static function get_bit_int($bit) {
        bindec('1' . str_repeat('0', $bit));
    }

    protected function sanitizeInput($val, $allow_html = false) {

        $val = stripslashes($val);

        if ($allow_html === true && $this->trusted) {
            $clean_html = Scrub::trustedHTML($val);
        } elseif ($allow_html === true) {
            $clean_html = Scrub::html($val);
        } elseif ($allow_html) {
            $clean_html = Scrub::html($val, $allow_html);
        } else {
            $clean_html = Scrub::text($val);
        }

        return $clean_html;
    }

    protected function encrypt($table, $column, $value) {
        // TODO: use remote AES encryption method for isolated HSM.
        $table_key = Configuration::get('lightning.table.encryption_key');
        return Encryption::aesEncrypt($value, $table_key);
    }

    public static function decrypt($data) {
        // TODO: use remote AES encryption method for isolated HSM.
        $table_key = Configuration::get('lightning.table.encryption_key');
        return Encryption::aesDecrypt($data, $table_key);
    }

    /**
     * getRow() gets a single entry from the table based on $this->id

     * Constructs a database query based on the following class variables:
     * @param string $table->table			the table to query
     * @param string $table->key		the table name of the parent link (foreign key table)
     * @param string $table->id		the id (foreign key) of the parentLink with which to link

     * @return stores result in $list class variable (no actual return result from the method)

     */
    protected function getRow($force = true) {
        if (!empty($this->prefixRows[$this->id])) {
            // If it's a fixed value.
            $this->editable = false;
            $this->deleteable = false;
            $this->list = $this->prefixRows[$this->id];
            return;
        } elseif ($force == false && count($this->list) == 0) {
            // If it's already loaded.
            return false;
        }

        $where = [];
        $this->getKey();

        if ($this->parentLink && $this->parentId) {
            $where[$this->parentLink] = $this->parentId;
        }
        if ($this->list_where != '') {
            $where = array_merge($this->list_where, $where);
        }
        if ($this->accessControl != '') {
            $where = array_merge($this->accessControl, $where);
        }
        $join = $this->getAccessTableJoins();

        if ($this->joins) {
            $join = array_merge($join, $this->joins);
        }

        $where[$this->getKey(true)] = $this->singularity ? $this->singularityID : $this->id;

        // fields we retrieve from the query
        $fields = array_merge(["{$this->table}.*"], $this->joinFields);

        if ($this->table) {
            $this->list = $this->database->selectRowQuery([
                'from' => $this->table,
                'join' => $join,
                'where' => $where,
                'select' => $fields
            ]);
        }
    }

    protected function getAccessTableJoins() {
        $join = [];
        if ($this->accessTable) {
            if ($this->accessTableJoin) {
                $join[] = $this->accessTableJoin;
            } else {
                $join[] = [
                    'join' => $this->accessTable,
                    'using' => $this->getKey()
                ];
            }
        }
        return $join;
    }

    /**
     * Build a query to select the list of entries.
     */
    protected function loadList() {

        // check for required variables
        if ($this->table == '') {
            return;
        }

        $query = [
            'select' => [$this->table => ['*']],
            'from' => $this->table,
            'join' => [],
            'where' => [],
            'indexed_by' => $this->getKey()
        ];

        // Add joins.
        $query['join'] = $this->getAccessTableJoins();
        // TODO: This should be simplified with Database::filterQuery();
        if ($this->joins) {
            $query['join'] = array_merge($query['join'], $this->joins);
            if (!empty($this->joinFields)) {
                $query['select'] = array_merge($query['select'], $this->joinFields);
            } else {
                foreach ($this->joins as $join) {
                    // Add default table joins.
                    $table = isset($join[1]) ? $join[1] : (isset($join['join']) ? $join['join'] : (isset($join['left_join']) ? $join['left_join'] : ''));
                    $query['select'][] = [$table => ['*']];
                }
            }
        }

        if ($this->parentLink && $this->parentId) {
            $query['where'][$this->parentLink] = $this->parentId;
        }
        if (!empty($this->list_where)) {
            $query['where'] = array_merge($this->list_where, $query['where']);
        }
        if ($this->action == "autocomplete" && $field = Request::post('field')) {
            $this->accessControl[$this->fullField($field)] = ['LIKE', Request::post('st') . '%'];
        }

        if ($this->cur_subset) {
            if ($this->subset[$this->cur_subset]) {
                $query['where'] = array_merge($this->subset[$this->cur_subset], $query['where']);
            }
        }

        if ($this->action == 'list' OR $this->action == 'export') {
            $this->additional_action_vars['ste'] = Request::get('ste');
            $this->filterQuery = Request::get('filter', Request::TYPE_ARRAY, Request::TYPE_ASSOC_ARRAY);

            // Add the text search.
            if (!empty($this->additional_action_vars['ste'])) {
                $query['where'][] = Database::getMultiFieldSearch(
                    $this->search_fields,
                    explode(' ', $this->additional_action_vars['ste']),
                    $this->searchWildcard
                );
            }

            // Add field filters.
            if (!empty($this->filterQuery)) {
                foreach ($this->filterQuery as $filter_values) {
                    if (!empty($this->filters[$filter_values['filter']])) {
                        $settings = $this->filters[$filter_values['filter']];
                        $filter = new $settings['class']($settings);
                        $filter_query = $filter->filterQuery($filter_values);
                        Database::filterQuery($query, $filter_query);
                    }
                }
            }
        }

        // Prepare the sort order.
        if (!empty($this->sort)) {
            if (is_array($this->sort)) {
                $query['order_by'] = $this->sort;
            } else {
                $query['order_by'] = [$this->sort => 'ASC'];
            }
        }

        if ($this->action == "autocomplete") {
            $query['fields'][] = [$this->getKey() => "`{$_POST['field']}`,`{$this->getKey()}`"];
            $query['order_by']['field'] = 'ASC';
        } else {
            $query['fields'][] = [$this->table => ['*']];
        }

        // Most important
        if (!empty($this->accessControl)) {
            $query['where'] = array_merge($this->accessControl, $query['where']);
        }

        // Limit to one entry per primary key of the original table.
        $query['group_by'] = $this->getKey(true);

        // Get the page count.
        $this->listCount = $this->database->countQuery($query + ['as' => 'query'], true);

        // Add limits
        $query += [
            'limit' => $this->maxPerPage,
            'page' => $this->page_number,
        ];

        // Get the list.
        $this->list = $this->database->selectQuery($query);
    }

    protected function loadFullListCursor() {
        $this->list = $this->database->select($this->table);
    }

    protected function executeTask() {
        // do we load a subset or ss vars?
        if (isset($_REQUEST['ss'])) {
            $this->cur_subset = Scrub::variable($_REQUEST['ss']);
        } elseif ($this->subset_default) {
            $this->cur_subset = $this->subset_default;
        }

        // if the table is not set explicitly, look for one in the url
        if (!isset($this->table)) {
            if (isset($_REQUEST['table'])) {
                $this->table = Request::get('table');
                $this->table_url = true;
            }
            else return false;
        }

        // see if we are calling an action from a link
        $action = Request::get('action');
        if ($action == "action" && isset($this->action_fields[$_GET['f']])) {
            switch ($this->action_fields[$_GET['f']]['type']) {
                case "function":
                    $this->id = Request::get('id');
                    $this->getRow();
                    $this->action_fields[$_GET['f']]['function']($this->list);
                    header("Location: " . $this->createUrl($_GET['ra'], $row[$this->getKey()]));
                    exit;
                    break;
            }
        }

        // check for a singularity, only allow edit/update (this means a user only has access to one of these entries, so there is no list view)
        if ($this->singularity) {
            $row = $this->database->selectRow($this->table, [$this->singularity => $this->singularityID]);
            if (count($row) > 0) $singularity_exists = true;
            if ($singularity_exists) $this->id = $row[$this->getKey()];
            // there can be no "new", "delete", "delconf", "list"
            if ($this->action == "new" || $this->action == "edit" || $this->action == "delete" || $this->action == "delconf" || $this->action == "list" || $this->action == '') {
                if ($singularity_exists)
                    $this->action = "edit";
                else
                    $this->action = "new";
            }
            // if there is no current entry, an edit becomes an insert
            if ($this->action == "update" || $this->action == "insert") {
                if ($singularity_exists)
                    $this->action = "update";
                else
                    $this->action = "insert";
            }
        }

        $this->getKey();
        switch ($this->action) {
            case 'pop_return': break;
            case 'autocomplete':
                $this->loadList();
                $output = ['list' => $this->list, 'search' => Request::post('st')];
                Output::json($output);
                exit;
                break;
            case 'file':
                $this->loadMainFields();
                $field = $_GET['f'];
                $this->getRow();
                if ($this->fields[$field]['type'] == 'file' && count($this->list) > 0) {
                    $file = $this->get_full_file_location($this->fields[$field]['location'], $this->list[$field]);
                    if (!file_exists($file)) die("No File Uploaded");
                    switch ($this->list[$this->fields[$field]['extension']]) {
                        case '.pdf':
                            Output::setContentType('application/pdf'); break;
                        case '.jpg': case '.jpeg':
                            Output::setContentType('image/jpeg'); break;
                        case '.png':
                            Output::setContentType('image/png'); break;
                    }
                    readfile($file);
                } else die ('config error');
                exit;
            case 'delete':
                if (!$this->deleteable) // FAILSAFE
                    break;
                if ($this->delconf)
                    break;
                $_POST['delconf'] = "Yes";
            case 'delconf':
                if (!$this->deleteable) // FAILSAFE
                    break;
                if ($_POST['delconf'] == "Yes") {
                }
            case 'list_action':
            case 'list':
            case '':
            default:
                $this->action = "list";
                break;
        }
    }

    protected function js_init_data() {
        JS::set('table_data.vars', []);
        if ($this->rowClick) {
            JS::set('table_data.rowClick', $this->rowClick);
            JS::set('table_data.action_file', $this->action_file);
            if (isset($this->table_url)) {
                JS::set('table_data.table', $this->table);
            }
            if ($this->parentLink) {
                JS::set('table_data.parentLink', $this->parentLink);
            }
            if ($this->parentId) {
                JS::set('table_data.parentId', $this->parentId);
            }
            if (count($this->additional_action_vars) > 0) {
                JS::set('table_data.vars', $this->additional_action_vars);
            }
        }
        $js_startup = '';
        foreach ($this->fields as $f => $field) {
            if (!empty($field['default_reset'])) {
                $table_data['defaults'][$f] = $field['default'];
            }
        }
        foreach ($this->links as $link => $link_settings) {
            if (
                !empty($link_settings['include_blank'])
                && (
                    (
                        $link_settings['include_blank'] == "if_empty"
                        && $link_settings['row_count'] == 0
                    )
                    || $link_settings['include_blank'] == "always"
                )
            ) {
                $js_startup .= 'new_subtable("' . $link . '");';
            }
        }

        if (!empty($this->search_fields) || !empty($this->links)) {
            JS::startup('lightning.table.init()');
        }

        if ($js_startup) {
            JS::startup($js_startup);
        }
    }

    /**
     * @param $dir
     * @param $file
     * @return mixed|string
     *
     * @deprecated
     */
    protected function get_full_file_location($dir, $file) {
        $f = $dir . "/" . $file;
        $f = str_replace("//", "/", $f);
        $f = str_replace("//", "/", $f);
        return $f;
    }

    protected function hasUploadfield() {
        foreach ($this->fields as $f) {
            if ($f['type'] == 'file' || $f['type'] == 'image') {
                return true;
            }
        }
    }

    protected function setPostedLinks() {
        foreach ($this->links as $link => $link_settings) {
            // FOR 1 (local) TO MANY (foreign)
            if (!empty($link_settings['type']) && $link_settings['type'] == 'image') {
                $filenames = Request::post('linked_images_' . $link_settings['table'], 'array', 'string');
                // Insert new links.
                $handler = $this->getFileHandler($link_settings);
                foreach ($filenames as &$filename) {
                    $filename = $handler->relativeFilename($filename);
                }
                $this->database->insertMultiple($link_settings['table'],
                    [
                        $link_settings['key'] => $this->id,
                        $link_settings['display_column'] => $filenames,
                    ],
                    true
                );
                // Remove old links.
                $this->database->delete($link_settings['table'],
                    [
                        $link_settings['key'] => $this->id,
                        $link_settings['display_column'] => ['NOT IN', $filenames],
                    ]
                );
            }
            elseif (!empty($link_settings['full_form'])) {
                if (!isset($this->list)) {
                    $this->getRow();
                }
                $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->getKey();
                $local_id = isset($this->list[$local_key]) ? $this->list[$local_key] : $this->id;

                if ($this->action == "update") {
                    // delete
                    $deleteable = preg_replace('/,$/', '', $_POST['delete_subtable_' . $link]);
                    if ($deleteable != '') {
                        $this->database->delete(
                            $link,
                            [$link_settings['key'] => ['IN', $deleteable], $local_key => $local_id]
                        );
                    }
                    // update
                    $list = $this->database->selectAll($link, [$local_key => $local_id], []);
                    foreach ($list as $l) {
                        foreach ($link_settings['fields'] as $f => $field) {
                            $link_settings['fields'][$f]['field'] = $f;
                            $link_settings['fields'][$f]['form_field'] = "st_{$link}_{$f}_{$l[$link_settings['key']]}";
                        }
                        $field_values = $this->getFieldValues($link_settings['fields']);
                        $this->database->update($link, $field_values, [$local_key => $local_id, $link_settings['key'] => $l[$link_settings['key']]]);
                    }
                }
                // insert new
                $new_subtables = explode(",", $_POST['new_subtable_' . $link]);
                foreach ($new_subtables as $i) if ($i != '') {
                    foreach ($link_settings['fields'] as $f => $field) {
                        $link_settings['fields'][$f]['field'] = $f;
                        $link_settings['fields'][$f]['form_field'] = "st_{$link}_{$f}_-{$i}";
                    }
                    $field_values = $this->getFieldValues($link_settings['fields']);
                    $this->database->insert($link, $field_values, [$local_key => $local_id]);
                }
            }
            elseif ($link_settings['index']) {
                // CLEAR OUT OLD SETTINGS
                $this->database->delete(
                    $link_settings['index'],
                    [$this->getKey() => $this->id]
                );

                // GET INPUT ARRAY
                $list = Request::get($link . '_input_array', Request::TYPE_EXPLODE, Request::TYPE_INT);
                foreach ($list as $l) {
                    $this->database->insert(
                        $link_settings['index'],
                        [
                            $this->getKey() => $this->id,
                            $link_settings['key'] => $l,
                        ]
                    );
                }
            }
        }
    }

    // print field or print editable field
    protected function printFieldValue($field, &$row = null, $html = true) {
        if (empty($row)) {
            $v = !empty($field['Value']) ? $field['Value'] : '';
        } else {
            $v = isset($row[$field['field']]) ? $row[$field['field']] : '';
        }

        if (!empty($field['encrypted'])) {
            $v = $this->decrypt($v);
        }

        // set the default value if new
        if ($this->action == "new" && isset($field['default']))
            $v = $field['default'];

        if (!empty($field['render_' . $this->action . '_field']) && is_callable($field['render_' . $this->action . '_field'])) {
            return $field['render_' . $this->action . '_field']($row);
        } elseif (!empty($field['display_value']) && is_callable($field['display_value'])) {
            return call_user_func_array($field['display_value'], [&$row]);
        } else {
            switch (preg_replace('/\([0-9]+\)/', '', $field['type'])) {
                case 'lookup':
                    // a lookup will translate to a value drawn from the lookup table based on the key value
                    if ($field['lookuptable'] && $field['display_column']) {
                        if ($v) {
                            $fk = isset($field['lookupkey']) ? $field['lookupkey'] : $field['field'];
                            $filter = [$fk => $v];
                            if (!empty($field['filter'])) {
                                $filter += $field['filter'];
                            }
                            if (!empty($field['accessControl'])) {
                                $filter = $field['accessControl'] + $filter;
                            }
                            // TODO: implement a cache or join in the main query to prevent multiple query execution.
                            $value = $this->database->selectRow(
                                $field['lookuptable'],
                                $filter,
                                [
                                    $field['display_column'], $fk
                                ]
                            );
                            return $value[$field['display_column']];
                        }
                    } else {
                        return $v;
                    }
                    break;
                case 'image':
                    $return = '';
                    if (!empty($v)) {
                        if ($html) {
                            $return = '<img src="' . $this->getImageLocationWeb($field, $v) . '" class="table_list_image" />';
                        } else {
                            return $this->getImageLocationWeb($field, $v);
                        }
                    }
                    return $return;
                case 'yesno':
                    $field['options'] = [1=>'No', 2=>'Yes'];
                case 'state':
                    if ($field['type'] == "state") {
                        $field['options'] = Location::getStateOptions();
                    }
                case 'select':
                    if (isset($field['options'][$v])) {
                        if (is_array($field['options'][$v])) {
                            return $field['options'][$v]['V'];
                        } else {
                            return $field['options'][$v];
                        }
                    } else {
                        foreach ($field['options'] as $sub_options) {
                            if (is_array($sub_options) && isset($sub_options[$v])) {
                                return $sub_options[$v];
                            }
                        }
                    }
                    return '';
                    break;
                case 'file':
                    // TODO: Display thumbmail.
                    break;
                case 'text':
                case 'mediumtext':
                case 'longtext':
                case 'div':
                case 'html':
                    if ($this->action == "list" || $this->action == "search") {
                        $v = strip_tags($v);
                        if (strlen($v) > 64)
                            return substr($v, 0, 64) . "...";
                        else
                            return $v;
                    }
                    else // edit should show full text
                        return $v;
                    break;
                case 'json':
                    return '<pre>' . json_encode(json_decode($v, true), JSON_PRETTY_PRINT) . '</pre>';
                case 'time':
                    return Time::printTime($v);
                    break;
                case 'date':
                    return Time::printDate($v, !empty($field['timezone']) ? $field['timezone'] : null);
                    break;
                case 'datetime':
                    return Time::printDateTime($v, !empty($field['timezone']) ? $field['timezone'] : null);
                    break;
                case 'checkbox':
                    if ($html) {
                        return '<input type="checkbox" disabled ' . (($v == 1) ? 'checked' : '') . ' />';
                    } else {
                        return ($v == 1) ? 'Yes' : 'No';
                    }
                    break;
                case 'checklist':
                    $vals = $this->decode_bool_group($v);
                    $output = '';
                    if ($html) {
                        foreach ($field['options'] as $i => $opt) {
                            if (is_array($opt)) {
                                $id = $opt[0];
                                $name = $opt[1];
                            } else {
                                $id = $i;
                                $name = $opt;
                            }
                            $output .= "<div class='checlist_item'><input type='checkbox' disabled " . (($vals[$id] == 1) ? "checked" : '') . " />{$name}</div>";
                        }
                    } else {
                        $output = [];
                        foreach ($field['options'] as $i => $opt) {
                            $output[] = is_array($opt) ? $opt[1] : $opt;
                        }
                        return implode(', ', $output);
                    }
                    return $output;
                    break;
                case 'note':
                    return $field['note'];
                    break;
                default:
                    return Scrub::toHTML($v);
                    break;

            }
        }
    }

    /**
     * Get a new random file name.
     *
     * @param string $extension
     *   The file from the $_FILE array.
     *
     * @return string
     *   The file name.
     */
    protected function getNewRandomImageName($extension) {
        return rand(0, 99999) . '.' . $extension;
    }

    /**
     * Get the uploaded file format.
     *
     * @param array $file
     *   The file from the $_FILE array.
     *
     * @return string
     *   The file name.
     */
    protected function getUploadedFileFormat($file) {
        switch (exif_imagetype($file['tmp_name'])) {
            case IMAGETYPE_PNG:  return 'png'; break;
            case IMAGETYPE_GIF:  return 'gif'; break;
            case IMAGETYPE_JPEG:
            default:             return 'jpg';
        }
    }

    /**
     * Get the output file format.
     *
     * @param array $field
     *   The field settings.
     * @param array $file
     *   The file from the $_FILE array.
     *
     * @return string
     *   The file name.
     */
    protected function getOutputFormat($field, $file) {
        if (!empty($file)) {
            $uploadedFormat = $this->getUploadedFileFormat($file);
        }

        if (empty($file)) {
            return '';
        }

        if (empty($field['format'])) {
            return $uploadedFormat;
        }

        if (is_array($field['format'])) {
            return in_array($uploadedFormat, $field['format']) ? $uploadedFormat : $field['format'][0];
        }

        return $field['format'];
    }

    /**
     * Get the location of a file from the web.
     *
     * @param array $field
     *   The field settings.
     * @param string $file
     *   The file name.
     *
     * @return string
     *   The web location.
     */
    protected function getImageLocationWeb($field, $file = '') {
        $images = $this->getCompositeImageArray($field);
        // See if there is an image marked as default.
        foreach ($images as $image) {
            if (!empty($image['default'])) {
                $image_data = $image;
                break;
            }
        }
        // If no image is selected, select the first image to display.
        if (empty($image_data)) {
            $image_data = $images[0];
        }

        if (!isset($image_data['conform_name']) || $image_data['conform_name'] !== false) {
            $file = $this->getFullFileName($file, $image_data, null);
            // This condition is temporary until all images are stored with extensions.
            if (empty($image_data['browser'])) {
                $file .= '.' . (!empty($image_data['format']) ? $image_data['format'] : 'jpg');
            }
        }

        $handler = $this->getFileHandler($image_data);
        return $handler->getWebURL($file);
    }

    /**
     * Render the edit field component.
     *
     * @param array $field
     *   The field settings.
     * @param array $row
     *   The data row.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderEditField($field, &$row = []) {
        // Make sure the form_field is set.
        if (!isset($field['form_field'])) {
            $field['form_field'] = $field['field'];
        }

        // Get the default field value.
        if (!empty($_POST)) {
            $v = Request::post($field['form_field']);
        }
        elseif (empty($row)) {
            $v = isset($field['default']) ? $field['default'] : '';
        }
        elseif (isset($field['edit_value'])) {
            if (is_callable($field['edit_value'])) {
                $v = $row[] = $field['edit_value']($row);
            } else {
                $v = $row[] = $field['edit_value'];
            }
        }
        elseif (!empty($row[$field['field']])) {
            $v = $row[$field['field']];
        }

        if (isset($this->preset[$field['field']]['render_' . $this->action . '_field'])) {
            $this->getRow(false);
            if (is_array($this->preset[$field['field']]['render_' . $this->action . '_field'])
                && $this->preset[$field['field']]['render_' . $this->action . '_field'][0] == 'this') {
                $this->preset[$field['field']]['render_' . $this->action . '_field'][0] = $this;
            }
            return $this->preset[$field['field']]['render_' . $this->action . '_field']($this->list);
        }

        // Prepare value.
        if (!isset($field['Value'])) {
            $field['Value'] = isset($v) ? $v : null;
        }
        if (!empty($field['encrypted'])) {
            $field['Value'] = $this->decrypt($field['Value']);
        }

        // Set the default value if new.
        if ($this->action == "new" && isset($field['default'])) {
            $field['Value'] = $field['default'];
        }

        // Print form input.
        $options = [];
        $return = '';
        switch (preg_replace('/\([0-9]+\)/', '', $field['type'])) {
            case 'text':
            case 'mediumtext':
            case 'longtext':
            case 'html':
                $config = [];
                if (empty($field['editor'])) {
                    $field['editor'] = HTMLEditor::TYPE_BASIC;
                }

                if (!empty($field['full_page'])) {
                    $config['fullPage'] = true;
                }
                if (!empty($field['url'])) {
                    $config['url'] = $field['url'];
                }

                if (!empty($field['full_page']) || $field['editor'] == HTMLEditor::TYPE_FULL || !empty($field['trusted']) || $this->trusted) {
                    $config['allowedContent'] = true;
                }

                if (!empty($field['height'])) {
                    $config['height'] = $field['height'];
                }
                if (!empty($field['upload'])) {
                    $config['browser'] = true;
                }
                $config['content'] = $field['Value'];
                $config['startup'] = true;

                // These prevent CKEditor from adding content which breaks bracketed markup.
                $config['fillEmptyBlocks'] = false;
                $config['ignoreEmptyParagraph'] = false;

                if (!empty($field['div'])) {
                    return HTMLEditor::div($field['form_field'], $config);
                } else {
                    return HTMLEditor::iframe($field['form_field'], $config);
                }
                break;
            case 'div':
                if ($field['Value'] == '')
                    $field['Value'] = "<p></p>";
                return "<input type='hidden' name='{$field['form_field']}' id='{$field['form_field']}' value='" . Scrub::toHTML($field['Value']) . "' />
							<div id='{$field['form_field']}_div' spellcheck='true'>{$field['Value']}</div>";
                break;
            case 'json':
                return JSONEditorView::render($field['form_field'], [], $field['Value']);
                break;
            case 'plaintext':
                return "<textarea name='{$field['form_field']}' id='{$field['form_field']}' spellcheck='true' cols='90' rows='10'>{$field['Value']}</textarea>";
                break;
            case 'hidden':
                return "<input type='hidden' name='{$field['form_field']}' id='{$field['form_field']}' value='" . Scrub::toHTML($field['Value']) . "' />";
                break;
            case 'image':
                if (!empty($field['Value'])) {
                    $image_url = $this->getImageLocationWeb($field, $field['Value']);
                }
            // Fall through.
            case 'file':
                if (!empty($field['browser'])) {
                    $return .= FileBrowser::render($field['form_field'], [
                        'image' => !empty($image_url) ? $image_url : '',
                        'class' => 'table_edit_image',
                        'container' => $field['container'],
                    ]);
                }
                else if (($field['Value'] != '' && (!isset($field['replaceable']) || empty($field['replaceable']))) || $field['Value'] == '') {
                    $return .= "<input type='file' name='{$field['form_field']}' id='{$field['form_field']}' />";
                }
                return $return;
                break;
            case 'time':
                return Time::timePop(
                    $field['form_field'],
                    $field['Value'],
                    !empty($field['allow_blank']),
                    !empty($field['timezone']) ? $field['timezone'] : null
                );
                break;
            case 'date':
                $return = Time::datePop(
                    $field['form_field'],
                    !empty($field['Value']) ? $field['Value'] : 0,
                    !empty($field['allow_blank']),
                    !empty($field['start_year']) ? $field['start_year'] : 0
                );
                return $return;
                break;
            case 'datetime':
                return Time::dateTimePop(
                    $field['form_field'],
                    $field['Value'],
                    !empty($field['allow_blank']),
                    isset($field['start_year']) ? $field['start_year'] : date('Y') - 10,
                    !empty($field['timezone']) ? $field['timezone'] : null
                );
                break;
            case 'lookup':
            case 'yesno':
            case 'state':
            case 'country':
            case 'radios':
            case 'select':
                if ($field['type'] == 'lookup') {
                    $filter = !empty($field['filter']) ? $field['filter'] : [];
                    if (!empty($field['accessControl'])) {
                        $filter = $field['accessControl'] + $filter;
                    }

                    $options = $this->database->selectColumn(
                        // todo: rename these for consistency.
                        $field['lookuptable'],
                        $field['display_column'],
                        $filter,
                        !empty($field['lookupkey']) ? $field['lookupkey'] : $field['field']
                    );
                }
                elseif ($field['type'] == 'yesno') {
                    $options = [1 => 'No', 2 => 'Yes'];
                }
                elseif ($field['type'] == 'state') {
                    $options = Location::getStateOptions();
                }
                elseif ($field['type'] == 'country') {
                    $options = Location::getCountryOptions();
                }
                else {
                    $options = $field['options'];
                }

                if (!is_array($options)) {
                    return false;
                }

                if (!empty($field['allow_blank'])) {
                    $options = ['' => ''] + $options;
                }

                if ($field['type'] == 'radios') {
                    $output = BasicHTML::radioGroup($field['form_field'], $options, $field['Value']);
                } else {
                    $output = BasicHTML::select($field['form_field'], $options, $field['Value']);
                }

                if (!empty($field['pop_add'])) {
                    // todo: this needs to require an explicit URL
                    if ($field['table_url']) $location = $field['table_url'];
                    else $location = "table.php?table=" . $field['lookuptable'];
                    $output .= "<a onclick='lightning.table.newPop(\"{$location}\",\"{$field['form_field']}\",\"{$field['display_column']}\")'>Add New Item</a>";
                }
                return $output;
                break;
            case 'range':
                $output = "<select name='{$field['form_field']}' id='{$field['form_field']}'>";
                if ($field['allow_blank'])
                    $output .= '<option value="0"></option>';
                if ($field['start'] < $field['end']) {
                    for ($k = $field['start']; $k <= $field['end']; $k++)
                        $output .= "<option value='{$k}'" . (($field['Value'] == $k) ? 'selected="selected"' : '') . ">{$k}</option>";
                }
                $output .= '</select>';
                return $output;
                break;
            case 'checkbox':
                $attribtues = [];
                if (!empty($field['disabled'])) {
                    $attribtues['disabled'] = true;
                }
                return Checkbox::render($field['form_field'], 1, $field['Value'] == 1, $attribtues);
                break;
            case 'note':
                return $field['note'];
                break;
            case 'checklist':
                $vals = $this->decode_bool_group($field['Value']);
                $output = '';
                foreach ($field['options'] as $i => $opt) {
                    if (is_array($opt)) {
                        $id = $opt[0];
                        $name = $opt[1];
                    } else {
                        $id = $i;
                        $name = $opt;
                    }
                    $output .= "<div class='checlist_item'><input type='checkbox' name='{$field['form_field']}_{$id}' value='1' " . (($vals[$id] == 1) ? "checked" : '') . " />{$name}</div>";
                }
                return $output;
                break;
            case 'varchar':
            case 'char':
                preg_match('/(.+)\(([0-9]+)\)/i', $field['type'], $array);
                $options['size'] = $array[2];
            default:
                if (!empty($field['autocomplete'])) {
                    $options['class'] = ['autocomplete_field'];
                    $options['data-name'] = $field['form_field'];
                    $options['data-type'] = 'field';
                    $options['autocomplete'] = false;
                }

                return BasicHTML::text($field['form_field'], $field['Value'], $options);
                break;
        }
    }
}
