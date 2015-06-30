<?php
/*
  "table" class
  provides basic interactivity with a database table (list, insert, update, delete)

  minimal setup:

	// Instanciate table interaction object
  	$myTableObject = new table();
  	// set the database table
	$myTableObject->table = "changeme_database_table";
	// set the primary key for the database table
	$myTableObject->getKey() = "changeme_table_primary_index";
    // process any database actions
	$myTableObject->execute_task();
	// output the database table to stdout
	$myTableObject->render_table();

  optional features: (set before ->execute_task() and ->render_table())

	// set the sortorder for the list of rows
	$myTableObject->sort = 'ORDER BY changeme_sort_column ASC, changeme_secondary_sort_column DESC';
	// set a (m-n) relationship between the table and another table, allowing the joining table to be managed
	// The foreign keys in the joining table must be named the same as in the joined tables' columns
	$myTableObject->link[changeme_related_table_name] = array('index'=>'changeme_join_table_name', 'key'=>'changeme_related_table_foreign_key_name', "display_name"=>"changeme_display_column_from_related_table", "list"=>true);
	// the relationship can even span to another database
	$myTableObject->link[changeme_related_table_name] = array('database'=>'changeme_related_table_database', 'index'=>'changeme_join_table_name', 'key'=>'changeme_related_table_foreign_key_name', "display_name"=>"changeme_display_column_from_related_table", "list"=>true);
	// set a column to be a lookup from another table (1-n relationship)
	// the lookuptable key column must be named the same as the table's column
 	$myTableObject->preset['changeme_table_foreign_key'] = array('type'=>'lookup', 'lookuptable'=>'changeme_related_table_name', 'display_name'=>'changeme_display_column_from_related_table');

	// set this to true to allow for serial updates
	$myTableObject->enable_serial_update = 1;

*/

namespace Lightning\Pages;

use Lightning\Tools\Cache\Cache;
use Lightning\Tools\Cache\FileCache;
use Lightning\Tools\CKEditor;
use Lightning\Tools\Configuration;
use Lightning\Tools\CSVIterator;
use Lightning\Tools\Database;
use Lightning\Tools\Form;
use Lightning\Tools\Image;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Scrub;
use Lightning\Tools\Security\Encryption;
use Lightning\Tools\Session;
use Lightning\Tools\Template;
use Lightning\View\Field;
use Lightning\View\Field\BasicHTML;
use Lightning\View\Field\Hidden;
use Lightning\View\Field\Location;
use Lightning\View\Field\Text;
use Lightning\View\Field\Time;
use Lightning\View\JS;
use Lightning\View\Page;

abstract class Table extends Page {

    protected $page = 'table';

    protected $table;
    protected $action;
    protected $function;
    protected $id = 0;
    protected $list;

    /**
     * Used when you want to set a value in the header (array).
     *
     * @var array
     */
    protected $template_vars;	//
    protected $preset = array();
    protected $trusted = false;
    protected $key;
    protected $delconf = true;
    protected $action_file;
    protected $defaultAction = 'list';
    protected $defaultIdAction = 'view';
    protected $fields = array();
    protected $links = array();
    protected $styles = array();
    protected $sort;
    protected $maxPerPage = 25;
    protected $listCount = 0;
    protected $page_number = 1;
    protected $action_fields=array();
    protected $custom_templates=array();
    protected $list_where;

    protected $importable = false;
    /**
     * @var FileCache
     */
    protected $importCache;

    /**
     * Criteria of elements editable by this object.
     *
     * @var array
     */
    protected $accessControl;

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
    protected $custom_template_directory = "table_templates/";
    protected $editable = true;
    protected $deleteable = true;
    protected $addable = true;
    protected $cancel = false;
    protected $searchable = false;

    /**
     * Whether the table is sortable.
     *
     * @var boolean
     */
    protected $sortable = true;

    /**
     * when generating a table (i.e. calendar table)
     * this key tells us what field is the trigger for creating a new TD
     *
     * @var string
     */
    protected $new_td_key = '';
    protected $calendar_month = 1;
    protected $calendar_year = '';
    protected $subset = Array();
    protected $search_fields = array();
    protected $searchWildcard = Database::WILDCARD_AFTER;
    protected $submit_redirect = true;
    protected $additional_action_vars = array();
    
    /**
     * Button names according to action type
     * @var array
     */
    protected $button_names = Array("insert"=>"Insert","cancel"=>"Cancel","update"=>"Update");

    /**
     * The list of actions perform after post request depending on type of the request
     * @var array
     */
    protected $action_after = Array("insert"=>"list","update"=>"list");

    /**
     * Extra buttons added to from. Array structure:
     * - type (type of the button out of available ones);
     * - text (text on the button);
     * - data (custom data);
     * @var Array
     */
    protected $custom_buttons = Array();

    /**
     * Available custom button types
     */
    const CB_SUBMITANDREDIRECT = 1;
    const CB_LINK = 2;
        
    protected $function_after = Array();
    protected $table_descriptions = "table_descriptions/";
    protected $singularity = false;
    protected $singularityID = 0;
    protected $parentLink;
    
    /*
     * Joined table
     */
    // Joined table name
    protected $accessTable;
    // ON clause
    protected $accessTableJoinOn;
    // extra WHERE condition
    protected $accessTableCondition;
    // Selected columns
    protected $accessTableColumns;
    // JOIN schema
    protected $accessTableSchema = "LEFT JOIN";
    
    protected $cur_subset;
    protected $join_where;
    protected $header;
    protected $table_url;
    protected $sort_fields;
    protected $parentId;
    protected $field_order;
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

    public function __construct($options = array()) {
        $this->calendar_year = date('Y');
        $this->calendar_month = date('m');
        // TODO: Action is not set yet. Is any of this necessary?
        if ($this->action == 'new') {
            $backlinkname = '';
            $backlinkvalue = '';
            // check for a backlink to be prepopulated in a new entry
            if (isset($_REQUEST['backlinkname'])) $backlinkname = $_REQUEST['backlinkname'];
            if (isset($_REQUEST['backlinkvalue'])) $backlinkvalue = $_REQUEST['backlinkvalue'];
            // must have both
            if ($backlinkname && $backlinkvalue) {
                $this->preset[$backlinkname] = array('default' => $backlinkvalue);
            }
        }
        if (isset($_POST['function'])) $this->function = $_POST['function'];
        if (isset($_REQUEST['id'])) $this->id = Request::get('id');
        if (isset($_REQUEST['p'])) $this->page_number = max(1, Request::get('p'));

        /*
         * serial_update comes as POST parameter
         */
        $this->serial_update = Request::post('serialupdate', 'boolean');
        
        $this->refer_return = Request::get('refer_return');

        // load the sort fields
        if ($sort = Request::get('sort')) {
            $field = explode(";", $sort);
            $this->sort_fields = array();
            $sort_strings = array();
            foreach($field as $f) {
                $f = explode(":", $f);
                if (!empty($f[1]) && $f[1] == "D") {
                    $this->sort_fields[$f[0]] = "D";
                    $sort_strings[] = "`{$f[0]}` DESC";
                } else {
                    $this->sort_fields[$f[0]] = "A";
                    $sort_strings[] = "`{$f[0]}` ASC";
                }
            }
            $this->sort = implode(",", $sort_strings);
        }

        $this->action_file = preg_replace('/\?.*/','', $_SERVER['REQUEST_URI']);

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }

        $this->initSettings();

        $this->fillDetaultSettings();

        Template::getInstance()->set('full_width', true);
        parent::__construct();
    }

    protected function initSettings() {}

    protected function fillDetaultSettings() {
        foreach ($this->links as $table => &$link_settings) {
            if (empty($link_settings['table'])) {
                $link_settings['table'] = $table;
            }
        }
    }

    public function get() {
        if ($this->singularity) {
            // The user only has access to a single entry. ID is irrelevant.
            $this->getEdit();
        } elseif (Request::query('id', 'int')) {
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
        $this->id = $this->singularity ? $this->singularityID : Request::query('id', 'int');

        if (!$this->id) {
            Messenger::error('Invalid ID');
            return;
        }
        if (!$this->editable) {
            Messenger::error('Access Denied');
            return;
        }
        $this->loadSingle();
    }

    public function getView() {
        $this->action = 'view';
        if (!$this->editable) {
            Messenger::error('Access Denied');
        }
        $this->get_row();
    }

    public function getNew() {
        $this->action = 'new';
        if (!$this->editable || !$this->addable) {
            Messenger::error('Access Denied');
        }
    }

    public function getPop() {
        if (!$this->editable || !$this->addable) {
            Messenger::error('Access Denied');
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

    public function getPopReturn() {
        $this->action = 'pop_return';
    }

    public function getImport() {
        $this->action = 'import';
        if (!$this->editable || !$this->addable || !$this->importable) {
            Messenger::error('Access Denied');
        }
    }

    public function postImport() {
        $this->action = 'import-align';
        $this->cacheImportFile();
        if (!$this->editable || !$this->addable || !$this->importable) {
            Messenger::error('Access Denied');
        }
    }

    public function postImportAlign() {
        $this->importDataFile();
        if (!$this->editable || !$this->addable || !$this->importable) {
            Messenger::error('Access Denied');
        }
        Navigation::redirect();
    }

    public function getList() {
        $this->action = 'list';
    }

    /**
     * Ajax search, outputs HTML table replacement.
     */
    public function getSearch() {
        $this->action = 'list';
        $this->loadMainFields();
        $this->loadList();
        Output::json(array('html' => $this->renderList(), 'd' => Request::get('i', 'int'), 'status' => 'success'));
    }

    public function getDelete() {
        $this->action = 'delete';
        if (!$this->editable || !$this->addable) {
            Messenger::error('Access Denied');
        }
    }

    public function getAutocomplete() {
        // Initialize some variables.
        $this->loadMainFields();
        $field = Request::get('field');
        $string = Request::get('st');
        $autocomplete = $this->fields[$field]['autocomplete'];

        $where = array();
        if (!empty($autocomplete['search'])) {
            if (!is_array($autocomplete['search'])) {
                $autocomplete['search'] = array($autocomplete['search']);
            }
            $where = Database::getMultiFieldSearch($autocomplete['search'], explode(' ', $string));
        }
        $results = Database::getInstance()->selectIndexed(
            $this->fields[$field]['autocomplete']['table'],
            $autocomplete['field'],
            $where,
            array(),
            'LIMIT 50'
        );

        if (!empty($autocomplete['display_value']) && is_callable($autocomplete['display_value'])) {
            array_walk($results, $autocomplete['display_value']);
        }
        Output::json(array('results' => $results));
    }

    public function postDelconf() {
        $this->action = 'delconf';
        if (!$this->editable || !$this->addable) {
            Output::error('Access Denied');
        }

        // loop through and delete any files
        $this->loadMainFields();
        $this->get_row();
        foreach($this->fields as $f=>$field) {
            if ($field['type'] == 'file' || $field['type'] == 'image') {
                if (file_exists($this->get_full_file_location($field['location'], $this->list[$f]))) {
                    unlink($this->get_full_file_location($field['location'], $this->list[$f]));
                }
            }
        }
        Database::getInstance()->delete($this->table, array($this->getKey() => $this->id));

        $this->afterPostRedirect();
    }

    public function postInsert() {
        $this->action = 'insert';
        if (!$this->addable) {
            Output::error('Access Denied');
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
        $this->id = Database::getInstance()->insert($this->table, $values, $this->update_on_duplicate_key ? $values : true);
        
        /*
         * Check if id is defined. If it's FALSE, there was an error 
         * inserting the new row. Probably duplicating.
         */
        if ($this->id == FALSE) {
            Output::error('There was a conflict with an existing entry.');
        }
        
        if (!empty($this->post_actions['after_insert'])) {
            $this->get_row();
            $this->post_actions['after_insert']($this->list);
        } elseif (!empty($this->post_actions['after_post'])) {
            $this->get_row();
            $this->post_actions['after_post']($this->list);
        }
        $this->set_posted_links();
        if (Request::get('pf')) {
            // if we are in a popup, redirect to the popup close script page
            Navigation::redirect($this->createUrl('pop-return', $this->id));
        }

        $this->afterInsert();

        $this->afterPostRedirect();
    }

    protected function afterInsert() {}

    public function postUpdate() {
        $this->id = Request::post('id', 'int');
        $this->action = 'update';
        if (!$this->editable) {
            Output::error('Access Denied');
        }

        // Update the record.
        $this->loadMainFields();
        $new_values = $this->getFieldValues($this->fields);
        if ($new_values === false) {
            return $this->getEdit();
        }

        if (!empty($new_values)) {
            Database::getInstance()->update($this->table, $new_values, array($this->getKey() => $this->id));
        }
        $this->update_accessTable();
        $this->set_posted_links();
        // If serial update is set, set the next action to be an edit of the next higest key,
        // otherwise, go back to the list.
        if (!empty($this->post_actions['after_update'])) {
            $this->get_row();
            $this->post_actions['after_update']($this->list);
        } elseif (!empty($this->post_actions['after_post'])) {
            $this->get_row();
            $this->post_actions['after_post']($this->list);
        }

        if ($this->enable_serial_update && $this->serial_update) {
            // Get the next id in the table
            $nextkey = Database::getInstance()->selectField(
                array('nextkey' => array('expression' => "MIN({$this->getKey()})")), 
                $this->table, 
                array(
                    $this->getKey() => array('>', $this->id)
                )
            );
            if ($nextkey) {
                $this->id = $nextkey;
                $this->get_row();
                $this->action_after['update'] = 'edit';
            } else {
                // No higher key exists, drop back to the list
                $this->serial_update = false;
            }
        }

        $this->afterUpdate();

        $this->afterPostRedirect();
    }

    protected function afterUpdate() {}

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
            Navigation::redirect($return);
        }

        if ($this->submit_redirect && $redirect = Request::get('redirect')) {
            Navigation::redirect($redirect);
        } elseif (!empty($this->redirectAfter[$this->action])) {
            Navigation::redirect($this->redirectAfter[$this->action]);
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

    public function execute() {
        // Setup the template.
        $template = Template::getInstance();
        $template->set('table', $this);
        $template->set('content', 'table');

        // Call the appropriate execution handler.
        parent::execute();
    }

    /**
     * Set this table to readonly mode.
     *
     * @param boolean $readOnly
     *   Whether this should be read only.
     */
    public function setReadOnly ($readOnly = true) {
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
     * Load a single entry.
     */
    protected function loadSingle() {
        $this->list = Database::getInstance()->selectRow($this->table, array($this->getKey() => $this->id));
    }

    /**
     * Get the primary key for the table.
     *
     * @return string
     *   The primary key name.
     */
    function getKey() {
        if (empty($this->key) && !empty($this->table)) {
            $result = Database::getInstance()->query("SHOW KEYS FROM `{$this->table}` WHERE Key_name = 'PRIMARY'");
            $result = $result->fetch();
            $this->key = $result['Column_name'];
        }
        return $this->key;
    }

    function render() {
        $this->loadMainFields();
        $this->check_default_rowClick();
        $this->renderHeader();

        if ($this->action == "new" && !$this->addable) {
            Output::error('Access Denied');
        }
        if ($this->action == "edit" && !$this->editable) {
            Output::error('Access Denied');
        }

        switch($this->action) {
            case 'pop_return':
                $this->render_pop_return();
                break;
            case 'view':
            case 'edit':
                $this->render_action_header();
            case 'new':
                $this->render_form();
                break;
            // DELETE CONFIRMATION
            case 'delete':
                if (!$this->deleteable) {
                    Output::error('Access Denied');
                    break;
                }
                echo $this->render_del_conf();
                break;
            case 'import':
                echo $this->renderImportFile();
                break;
            case 'import-align':
                echo $this->renderAlignmentForm();
                break;
            case 'list':
            default:
                if ($this->searchable)
                    echo $this->renderSearchForm();
                $this->loadList();
                $this->render_action_header();
                echo '<div class="table_list">';
                echo $this->renderList();
                echo '</div>';
                break;

        }
        // TODO: update to use the JS class.
        // we shouldn't need to call this as long as we use the JS class.
        $this->js_init_data();
    }

    protected function renderImportFile() {
        return '<form action="' . $this->getRedirectURL() . '" method="post" enctype="multipart/form-data">' . Form::renderTokenInput() . '<input type="hidden" name="action" value="import"><input type="file" name="import-file" /><input type="submit" name="submit" value="Submit" class="button"></form>';
    }

    protected function renderAlignmentForm() {
        $csv = new CSVIterator($this->importCache->getFile());
        $header_row = $csv->current();
        $output = '<form action="" method="POST">' . Form::renderTokenInput();
        $output .= '<input type="hidden" name="action" value="import-align">';
        $output .= '<input type="hidden" name="cache" value="' . $this->importCache->getReference() . '" />';
        $output .= '<table><thead><tr><td>Field</td><td>From CSV Column</td></tr></thead>';

        $input_select = BasicHTML::select('%%', array('-1' => '') + $header_row);

        $fields = $this->get_fields($this->table, $this->preset);
        foreach ($fields as $field) {
            if ($field['field'] != $this->getKey()) {
                $output .= '<tr><td>' . $field['display_name'] . '</td><td>'
                    . preg_replace('/%%/', 'alignment[' . $field['field'] . ']', $input_select) . '</td></tr>';
            }
        }

        $output .= '</table><label><input type="checkbox" name="header" value="1" /> First row is a header, do not import.</label>';

        if (method_exists($this, 'customImportFields')) {
            $output .= $this->customImportFields();
        } elseif (!empty($this->customImportFields)) {
            $output .= $this->customImportFields;
        }

        $output .= '<input type="submit" name="submit" value="Submit" class="button" />';

        $output .= '</form>';
        return $output;
    }

    /**
     * Load the uploaded import file into cache and parse it for input variables.
     */
    protected function cacheImportFile() {
        // Cache the uploaded file.
        $this->importCache = new FileCache();
        $this->importCache->setName('table import ' . microtime());
        $this->importCache->moveFile('import-file');
    }

    /**
     * Process the data and import it based on alignment fields.
     */
    protected function importDataFile() {
        $cache = new FileCache();
        $cache->loadReference(Request::post('cache'));
        if (!$cache->isValid()) {
            Output::error('Invalid reference. Please try again.');
        }

        // Load the CSV, skip the first row if it's a header.
        $csv = new CSVIterator($cache->getFile());
        if (Request::post('header', 'int')) {
            $csv->next();
        }

        // Process the alignment so we know which fields to import.
        $alignment = Request::get('alignment', 'keyed_array', 'int');
        $fields = array();
        foreach ($alignment as $field => $column) {
            if ($column != -1) {
                $fields[$field] = $column;
            }
        }

        $database = Database::getInstance();

        $values = array();
        while ($csv->valid()) {
            $row = $csv->current();
            foreach ($fields as $field => $column) {
                $values[$field][] = $row[$column];
            }

            if (count($values[$field]) >= 100) {
                // Insert what we have so far and continue.
                $last_id = $database->insertSets($this->table, array_keys($fields), $values, true);
                if (method_exists($this, 'customImportPostProcess')) {
                    $ids = $last_id ? range($last_id - $database->affectedRows() + 1, $last_id) : [];
                    $this->customImportPostProcess($values, $ids);
                }
                $values = array();
            }

            $csv->next();
        }

        if (!empty($values)) {
            $last_id = $database->insertSets($this->table, array_keys($fields), $values, true);
            if (method_exists($this, 'customImportPostProcess')) {
                $ids = $last_id ? range($last_id - $database->affectedRows() + 1, $last_id) : [];
                $this->customImportPostProcess($values, $ids);
            }
        }
    }

    protected function renderSearchForm() {
        // @todo namespace this
        JS::inline('table_search_i=0;table_search_d=0;');
        return 'Search: <input type="text" name="table_search" value="' . Scrub::toHTML(Request::get('ste')) . '" onkeyup="lightning.table.search(this);" />';
    }

    function render_pop_return() {
        $this->get_row();
        $send_data = array(
            'pf' => Request::get('pf'),
            'id' => $this->id,
            'pfdf' => $this->list[Request::get('pfdf')]
        );
        JS::startup('lightning.table.returnPop('.json_encode($send_data).')');
    }

    function render_calendar_list() {
        $this->js_init_calendar();
        $this->loadMainFields();
        $this->renderHeader();
        $last_date = 0;
        foreach($this->list as $l) {
            if ($last_date != $l['date']) {
                $last_date = $l['date'];
                echo "<div class='list_date_header'>".$this->print_nice_date($l['date'])."</div>";
            }
            echo "<div class='list_cal_item' class='office_calendar {item_color}'>";
            if ($l['evt_type'] == 1) {
                $this_item_output = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_t']);
            } elseif ($l['evt_type'] == 2) {
                $this_item_output = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_d']);
            } else {
                $this_item_output = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_c']);
            }
            foreach($this->fields as $field) {
                $this_item_output = str_replace('{'.$field['field'].'}', $this->print_field_value($field, $l), $this_item_output);
            }
            echo $this_item_output;
            echo "</div>";
        }
    }

    function print_nice_date($jd) {
        // format of Thursday, Septemeber 22nd 2012
        if ($jd == 0) return '';
        $date = explode("/",JDToGregorian($jd));
        $output = jddayofweek($jd,1).", ".jdmonthname($jd,3)." {$date[1]}, {$date[2]}";
        return $output;
    }

    function render_calendar_table($get_new_list = true) {
        $this->js_init_calendar();
        if ($get_new_list)
            $this->loadList();
        $this->loadMainFields();
        $this->renderHeader();
        $today = GregorianToJD(date("m"), date("d"), date("Y"));


        // create index for fast access
        $date_index = array();
        foreach($this->list as $li) {
            if (!is_array($date_index[$li['date']]))
                $date_index[$li['date']] = Array();
            $date_index[$li['date']][] = $li;
        }

        if (isset($this->custom_templates[$this->action.'_item_t'])) {
            $calendar_month = $this->calendar_month;
            $calendar_year = $this->calendar_year;

            echo "<table border='0' class='table_calendar' cellpadding='0' cellspacing='0'><tr class='header_row'><td>Sunday</td><td>Monday</td><td>Tuesday</td><td>Wednesday</td><td>Thursday</td><td>Friday</td><td>Saturday</td></tr>\n<tr>";
            $all_month_days = array(31, 28+date("L", mktime(0, 0, 0, 2, $this->calendar_year)), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
            $num_days = $all_month_days[$this->calendar_month - 1];


            $all_month_days = array(31, 28+date("L", mktime(0, 0, 0, 1, 1, $calendar_year)), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
            $num_days = $all_month_days[$calendar_month - 1];


            $day_in_week = date("w", mktime(0, 0, 0, $calendar_month, 1, $calendar_year));

            // fill in start blanks
            for ($i = 0; $i < $day_in_week; $i++) {
                echo "\n<td>&nbsp;</td>";
            }


            // loop through each date and print output
            $i = 1;
            while ($i <= $num_days) {
                $day_in_week = ($day_in_week + 1) % 7;

                $t = mktime(0, 0, 0, $calendar_month, $i, $calendar_year);
                $today_class = ($today == GregorianToJD($calendar_month, $i, $calendar_year)) ? 'today' : '';

                echo "\n<td class='actv {$today_class}'><p class='date'>$i";
                if ($this->editable)
                    echo "<a href='".$this->createUrl("new",0,'',array("date"=>gregoriantojd($calendar_month, $i, $calendar_year))).
                        "' /><img src='/images/lightning/new.png' border='0' /></a>";
                echo "</p><div class='events'>"
                    . $this->render_calendar_items($date_index[GregorianToJD($calendar_month, $i, $calendar_year)])
                    . "</div></td>";
                if ($day_in_week == 0) {
                    echo "\n</tr>\n<tr>";
                }

                $i++;
            }
            echo "\n</tr>\n</table>";
        }
    }

    function render_calendar_items(&$list) {
        $template_t = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_t']);
        $template_c = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_c']);
        $template_d = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_d']);
        $ret_str = '';

        if (!is_array($list)) return;
        foreach($list as $row) {
            // load the template
            if ($row['evt_type'] == 1)
                $this_item_output = $template_t;
            elseif ($row['evt_type'] == 2)
                $this_item_output = $template_d;
            else
                $this_item_output = $template_c;

            // replace variables
            foreach($this->fields as $field) {
                $this_item_output = str_replace('{'.$field['field'].'}', $this->print_field_value($field, $row), $this_item_output);
            }
            $this_item_output = $this->template_item_vars($this_item_output, $row[$this->getKey()]);
            $ret_str .= $this_item_output;
        }
        return $ret_str;
    }

    function render_del_conf() {
        // get delete confirmation
        $output = "<br /><br />Are you sure you want to delete this?<br /><br /><form action='' method='POST'>";
        $output .= Form::renderTokenInput();
        $output .= "<input type='hidden' name='id' value='{$this->id}' />
            <input type='hidden' name='action' value='delconf' />
            <input type='submit' name='delconf' value='Yes' class='button'/>
            <input type='submit' name='delconf' value='No' class='button' />";
        if ($this->refer_return) {
            $output .= '<input type="hidden" name="refer_return" value="'.$this->refer_return.'" />';
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
        if (is_array($this->template_vars)) {
            foreach ($this->template_vars as $row) {

            }
        }

        if (isset($this->custom_templates[$this->action.'_header'])) {
            $template = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_header']);
            $template = $this->template_item_vars($template, $this->id);
        } elseif ($this->header != '') {
            $template = "<h1>{$this->header}</h1>";
        }

        if (is_array($this->template_vars)) {
            foreach ($this->template_vars as $key=>$val) {
                $template = str_replace('{'.$key.'}', $val, $template);
            }
        }
        echo !empty($template) ? $template : '';
    }

    public function render_action_header() {
        if (isset($this->custom_templates[$this->action.'_action_header'])) {
            if ($this->custom_templates[$this->action.'_action_header'] != '')
                echo $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_action_header']);
        } else {
            if ($this->addable) {
                echo "<a href='".$this->createUrl('new') . "'><img src='/images/lightning/new.png' border='0' title='Add New' /></a>";
            }
            if ($this->importable) {
                echo "<a href='".$this->createUrl('import') . "'><img src='/images/lightning/send_doc.png' border='0' title='Import' /></a><br />";
            }
            if ($this->addable || $this->importable) {
                echo '<br />';
            }
        }
    }

    public function renderList() {

        if (count($this->list) == 0 && empty($this->prefixRows)) {
            return "<p></p><p></p><p>There is nothing to show. <a href='".$this->createUrl('new')."'>Add a new entry</a></p><p></p><p></p>";
        }

        $output = '';
        if (isset($this->custom_templates[$this->action.'_item'])) {
            // load the template
            $template = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item']);
            foreach($this->list as $row) {
                // create temp instance for this row
                $this_item_output = $template;
                // replace variables
                foreach($this->fields as $field) {
                    if ($this->which_field($field)) {
                        $this_item_output = str_replace('{'.$field['field'].'}', $this->print_field_value($field, $row), $this_item_output);
                    }
                }
                // replace functional links
                $this_item_output = $this->template_item_vars($this_item_output, $row[$this->getKey()]);
                $output .= $this_item_output;
            }
            return $output;
        } else {
            // show pagination
            $output .= $this->pagination();
            // if there is something to list
            if (count($this->list) > 0 || !empty($this->prefixRows)) {

                // add form if required
                if ($this->action_fields_requires_submit()) {
                    $output .= "<form action='".$this->createUrl()."' method='POST'>";
                    $output .= Form::renderTokenInput();
                }
                $output.= "<div class='list_table_container'>";
                $output.= '<table cellspacing="0" cellpadding="3" border="0" width="100%">';

                // SHOW HEADER
                $output.= "<thead><tr>";
                $output .= $this->renderListHeader();

                // SHOW ACTION HEADER
                $output.= $this->render_action_fields_headers();
                $output.= "</tr></thead>";

                // Initialize the click handler.
                if (!empty($this->rowClick)) {
                    switch($this->rowClick['type']) {
                        case 'url':
                        case 'action':
                            JS::startup('$(".table_list").on("click", "tr", lightning.table.click)');
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
                    '<input type="submit" name="submit" value="Submit" class="button" />';
                }
                $output.= "</table></div>";
                if ($this->action_fields_requires_submit())
                    $output.= "</form>";
                $output.= $this->pagination();
            }
            return $output;
        }
    }

    function renderListHeader() {
        $output = '';
        // If the field order is specified.
        if (is_array($this->field_order)) {
            foreach($this->field_order as $f) {
                if (isset($this->fields[$f])) {
                    if ($this->which_field($this->fields[$f])) {
                        if ($this->sortable)
                            $output .= "<td><a href='".$this->createUrl('list',0,'',array('sort'=>array($f=>'X')))."'>{$this->fields[$f]['display_name']}</a></td>";
                        else
                            $output.= "<td>{$this->fields[$f]['display_name']}</td>";
                    }
                } elseif (isset($this->links[$f])) {
                    if (!empty($this->links[$f]['list']) && $this->links[$f]['list'] == 'compact') {
                        if ($this->links[$f]['display_name']) {
                            $output.= "<td>{$this->links[$f]['display_name']}</td>";
                        } else {
                            $output.= "<td>{$f}</td>";
                        }
                    }
                }
            }
        } else {
            // The field order is not specified.
            foreach($this->fields as $f=>&$field) {
                if ($this->which_field($field)) {
                    if ($this->sortable) {
                        $output .= "<td><a href='".$this->createUrl('list',0,'',array('sort'=>array($f=>'X')))."'>{$field['display_name']}</a></td>";
                    } else {
                        $output.= "<td>{$field['display_name']}</td>";
                    }
                }
            }
            // Add the linked tables.
            foreach($this->links as $l => $v) {
                if (!empty($v['list']) && $v['list'] == 'compact') {
                    if (!empty($v['display_name'])) {
                        $output.= "<td>{$v['display_name']}</td>";
                    } else {
                        $output.= "<td>{$l}</td>";
                    }
                }
            }
        }
        return $output;
    }

    function renderListRows($list, $editable) {
        $output = '';
        // loop through DATA rows
        foreach($list as $row) {
            // prepare click action for each row
            $output .= "<tr id='{$row[$this->getKey()]}'>";
            // SHOW FIELDS AND VALUES
            foreach($this->fields as &$field) {
                if ($this->which_field($field)) {
                    if (!empty($field['align'])) {
                        $output.= "<td align='{$field['align']}'>";
                    } else {
                        $output.= '<td>';
                    }
                    $output .= $this->print_field_value($field, $row);
                    $output .= '</td>';
                }
            }
            // LINKS w ALL ITEMS LISTED IN ONE BOX
            $output .= $this->renderLinkList($row);

            // EDIT, DELETE, AND OTHER ACTIONS
            $output .= $this->render_action_fields_list($row, $editable);

            // CLOSE MAIN DATA ROW
            $output .= "</tr>";

            // LINKS EACH ITEM GETS ITS OWN ROW
            $output .= $this->render_linked_table($row);
        }
        return $output;
    }

    function action_fields_requires_submit() {
        foreach($this->action_fields as $a=>$action) {
            if ($action['type'] == "checkbox") return true;
        }
    }

    // Called when rendering lists
    function renderLinkList(&$row) {
        $output = '';
        foreach($this->links as $link => $link_settings) {
            if (!empty($link_settings['list']) && $link_settings['list'] == 'compact') {
                if (!empty($link_settings['index'])) {
                    // There is a link table joining them. (Many to many) {
                    $links = $this->load_all_active_list($link_settings, $row[$this->getKey()]);
                }
                else {
                    $links = Database::getInstance()->select($link, array($this->getKey() => $row[$this->getKey()]));
                }

                $output .= '<td>';
                $displays = array();
                if (isset($link_settings['list']) == 'compact') {
                    foreach($links as $l)
                        if (!empty($link_settings['fields']) && is_array($link_settings['fields'])) {
                            $display = $link_settings["display"];
                            foreach($link_settings['fields'] as $f=>$a) {
                                if (!isset($a['field'])) $a['field'] = $f;
                                $display = str_replace('{'.$f.'}', $this->print_field_value($a, $l), $display);
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
    function render_linked_table(&$row) {
        $output = "<tr class='linked_list_container_row'><td colspan='" . $this->getMainTableColumnCount() . "'><table width='100%'>";
        foreach($this->links as $link => $link_settings) {
            if (!empty($link_settings['list']) && $link_settings['list'] === "each") {
                $link_settings['fields'] = $this->get_fields($link_settings['table'], $link_settings['preset']);
                $links = $this->load_all_active_list($link_settings, $row[$this->getKey()] );

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
                foreach($links as $row) {
                    $output.= "<tr id='link_{$link}_{$row[$link_settings['key']]}' class='linked_list_row'>";
                    foreach($link_settings['fields'] as $field_name => $field) {
                        $output .= '<td>' . $this->print_field_value($field, $row) . '</td>';
                    }
                    if (!empty($link_settings['edit_link'])) {
                        $output.= "<td><a href='{$link_settings['edit_link']}{$joinchar}action=edit&id={$row[$link_settings['key']]}'>Edit</a> <a href='{$link_settings['edit_link']}{$joinchar}action=delete&id={$row[$link_settings['key']]}'><img src='/images/lightning/remove.png' border='0' /></a></td>";
                    }
                    if (!empty($link_settings['edit_js'])) {
                        $output.= "<td><a href='' onclick='{$link_settings['edit_js']}.editLink({$row[$link_settings['key']]})'>Edit</a> <a href='' onclick='{$link_settings['edit_js']}.deleteLink({$row[$link_settings['key']]})'><img src='/images/lightning/remove.png' border='0' /></a></td>";
                    }
                    $output.= "</tr>";
                }
            }
        }
        $output.= "</table></td></tr>";
        return $output;
    }

    function render_action_fields_headers() {
        $output = '';
        foreach($this->action_fields as $a=>$action) {
            $output.= "<td>";
            if (isset($action['column_name']))
                $output.= $action['column_name'];
            elseif (isset($action['display_name']))
                $output.= $action['display_name']; else $output.= $a;
            switch($action['type']) {
                case 'link':
                case 'html':
                    break;
                case "checkbox":
                default:
                    if (!isset($action['check_all']) || empty($action['check_all'])) {
                        $output.= "<input type='checkbox' name='taf_all_{$a}' id='taf_all_{$a}' value='1' onclick=\"lightning.table.selectAll('{$a}');\" />";
                    }
                    break;
            }
            $output.= "</td>";
        }
        if ($this->editable !== false) {
            $output.= "<td>Edit</td>";
        }
        if ($this->deleteable !== false) {
            $output.= "<td>Delete</td>";
        }
        return $output;
    }

    function render_action_fields_list(&$row, $editable) {
        $output='';
        foreach($this->action_fields as $a=>$action) {
            $output.= "<td>";
            switch($action['type']) {
                case "function":
                    // Have table call a function.
                    $output.= "<a href='".$this->createUrl("action", $row[$this->getKey()], $a,array("ra"=>$this->action))."'>{$action['display_name']}</a>";
                    break;
                case "link":
                    $output.= "<a href='{$action['url']}{$row[$this->getKey()]}'>{$action['display_value']}</a>";
                    break;
                case 'html':
                    // Render the HTML.
                    $output .= is_callable($action['html']) ? $action['html']($row) : $action['html'];
                    break;
                case "action":
                    $output.= "<a href='".$this->createUrl($action['action'], $row[$this->getKey()], $action['action_field'])."'>{$action['display_name']}</a>";
                    break;
                case "checkbox":
                default:
                    $output.= "<input type='checkbox' name='taf_{$a}[{$row[$this->getKey()]}]' class='taf_{$a}' value='1' />";
                    break;
            }
            $output.= "</td>";
        }
        if ($this->editable !== false) {
            $output .= "<td>";
            if ($editable) {
                $output .= "<a href='" . $this->createUrl("edit", $row[$this->getKey()]) . "'><img src='/images/lightning/edit.png' border='0' /></a>";
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
     * @param boolean $return
     *   Whether to return the ouptut or send it to the user.
     *
     * @return null|string
     *   The fully rendered HTML content.
     */
    function render_form($return = false) {
        if (isset($this->custom_templates[$this->action.'_item'])) {
            $template = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item']);
            foreach($this->fields as $field) {
                switch($this->which_field($field)) {
                    case 'edit':
                        $template = str_replace('{'.$field['field'].'}', $this->renderEditField($field, $this->list), $template);
                        break;
                    case 'display':
                        $template = str_replace('{'.$field['field'].'}', $this->print_field_value($field, $this->list), $template);
                        break;
                    case false:
                        $template = str_replace('{'.$field['field'].'}', '', $template);
                }
            }
            $template = $this->template_item_vars($template, $this->id);
            if ($return)
                return $template;
            else
                echo $template;
        } else {

            // show form
            if ($this->action == "view") {

            }
            if ($this->action == "new") {
                $new_action = "insert";
            } else {
                $new_action = "update";
            }
            if ($this->action != "view") {
                $multipart_header = $this->hasUploadfield() ? "enctype='multipart/form-data'" : '';
                echo "<form action='".$this->createUrl()."' id='form_{$this->table}' method='POST' {$multipart_header}><input type='hidden' name='action' id='action' value='{$new_action}' />";
                echo Form::renderTokenInput();
                if ($return = Request::get('return', 'urlencoded')) {
                    echo Hidden::render('table_return', $return);
                }
            }
            // use the ID if we are editing a current one
            if ($this->action == "edit") {
                echo '<input type="hidden" name="id" id="id" value="' . $this->id . '" />';
            }
            if ($this->action == "view" && !$this->readOnly) {
                if ($this->editable !== false) {
                    echo "<a href='".$this->createUrl('edit', $this->id)."'><img src='/images/lightning/edit.png' border='0' /></a>";
                }
                if ($this->deleteable !== false) {
                    echo "<a href='".$this->createUrl('delete', $this->id)."'><img src='/images/lightning/remove.png' border='0' /></a>";
                }
            }
            $style = !empty($this->styles['form_table']) ? "style='{$this->styles['form_table']}'" : '';
            echo "<table class='table_form_table' {$style}>";
            unset ($style);
            if (is_array($this->field_order)) {
                foreach($this->field_order as $f) {
                    echo $this->render_form_row($this->fields[$f], $this->list);
                }
            } else {
                foreach($this->fields as $f=>$field) {
                    echo $this->render_form_row($this->fields[$f], $this->list);
                }
            }

            $this->render_form_linked_tables();

            // Render all submission buttons
            $this->renderButtons($new_action);
        }
    }

    /**
     * Renders all submission buttons of the form.
     * Depending on action performing, it will output also 
     * other types of buttons, plus any needed fields and links
     *
     * @param string $new_action
     *   Alternative name of the action processing
     */
    protected function renderButtons($new_action) {
        /*
         * Submit button has name parameter as 'sbmt' by purpose.
         * When it is 'submit', form doesn't get submitted by javascript
         * submit() function.
         */
        echo '<tr><td colspan="2">';
        if ($this->action != 'view') {
            echo '<input type="submit" name="sbmt" value="' . $this->button_names[$new_action] . '" class="button">';
        }

        // If exist render all custom buttons
        $this->renderCustomButtons();

        if ($this->action != 'view') {
            if ($this->cancel) {
                echo "<input type='button' name='cancel' value='{$this->button_names['cancel']}' onclick='document.location=\"".$this->createUrl()."\";' />";
            }
            if ($this->refer_return) {
                echo '<input type="hidden" name="refer_return" value="'.$this->refer_return.'" />';
            }
            if ($new_action == 'update' && $this->enable_serial_update) {
                echo '<input type="checkbox" name="serialupdate" value="true" checked="checked" /> Edit Next Record';
            }
            echo $this->form_buttons_after;
        }
        echo '</td></tr>';
        echo '</table>';
        if ($this->action != 'view') {
            echo '</form>';
        }
        if ($this->action == "view" && !$this->readOnly) {
            if ($this->editable !== false)
                echo "<a href='".$this->createUrl('edit', $this->id)."'><img src='/images/lightning/edit.png' border='0' /></a>";
            if ($this->deleteable !== false)
                echo "<a href='".$this->createUrl('delete', $this->id)."'><img src='/images/lightning/remove.png' border='0' /></a>";
        }
    }

    /**
     * Outputs custom buttons depending on its type
     * 
     * @return boolean
     *   If there's no custom buttons, just exit the function
     */
    protected function renderCustomButtons() {
        
        if (empty($this->custom_buttons)) {
            return FALSE;
        }

        /*
         * In case of there're a few buttons, set the different ids to them
         * by adding a ppostfix
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
                    $this->renderSubmitAndRedirect($button, $button_id);
                    break;
                case self::CB_LINK:
                    $download = !empty($button['download']) ? 'download="' . $button['download'] . '"' : '';
                    echo '<a href="' . $button['url'] . '" ' . $download . ' class="button">' . $button['text'] . '</a>';
            }
        }
    }

    /**
     * Renders button which submit the form and redirect user to a specified 
     * link
     * 
     * @param array $button
     *   Button data
     * @param string $button_id
     *   Button id which would be used and postfix in html id parameter
     */
    protected function renderSubmitAndRedirect($button, $button_id) {
        // Output the button.
        echo "<input id='custombutton_{$button_id}' type='submit' name='submit' value='{$button['text']}' class='button'/>";
    }
    
    function render_form_row(&$field, $row) {
        $output = '';
        if ($which_field = $this->which_field($field)) {
            if (isset($f['type'])) {
                $field['type'] = $this->fields[$field['field']]['type'];
            }
            // double column width row
            if ($field['type'] == "note") {
                if ($field['note'] != '') {
                    $output .= "<tr><td colspan='2'><h3>{$field['note']}</h3></td></tr>";
                } else {
                    $output .= "<tr><td colspan='2'><h3>{$field['display_name']}</h3></td></tr>";
                }
            } elseif (!empty($field['width']) && $field['width']=="full") {
                $output .= "<tr><td colspan='2'>{$field['display_name']}</td></tr>";
                $output .= "<tr><td colspan='2'>";
                // show the field
                if ($which_field == "display") {
                    $output .= $this->print_field_value($field, $row);
                } elseif ($which_field == "edit") {
                    $output .= $this->renderEditField($field, $row);
                }
                if ($field['default_reset']) {
                    $output .= "<input type='button' value='Reset to default' onclick='reset_field_value(\"{$field['field']}\");' />";
                }
                $output .= "</td></tr>";
                // column for title and column for field
            } else {
                $output .= "<tr><td valign='top'>";
                $output .= $field['display_name'];
                $output .= "</td><td valign='top'>";
                // show the field
                if ($which_field == "display") {
                    $output .= $this->print_field_value($field, $row);
                } elseif ($which_field == "edit") {
                    $output .= $this->renderEditField($field, $row);
                    if (!empty($field['note'])) {
                        $output .= $field['note'];
                    }
                }
                if (!empty($field['default_reset'])) {
                    $output .= "<input type='button' value='Reset to default' onclick='reset_field_value(\"{$field['field']}\");' />";
                }
                $output .= "</td></tr>";
            }
        }
        return $output;
    }

    // THIS IS CALLED TO RENDER LINKED TABLES IN view/edit/new MODE
    // (full form)
    function render_form_linked_tables() {
        foreach($this->links as $link => &$link_settings) {
            if (empty($link_settings['table'])) {
                $link_settings['table'] = $link;
            }
            if (!empty($link_settings['list']) && $link_settings['list'] === 'each') {
                // is this needed in form view?
                // LOAD THE LIST
                /*
                                $this->load_all_active_list($link_settings);
                                $link_settings['row_count'] = count($link_settings['active_list']);


                                // Set the character to join the URL parameters to the edit_link
                                $joinchar = (strpos($link_settings['edit_link'], $joinchar) !== false) ? ":" : '?';

                                if ($link_settings['display_name'])
                                    echo "<tr {$style}><td>{$link_settings['display_name']}".($link_settings['edit_link'] ? " <a href='{$link_settings['edit_link']}{$joinchar}action=new&backlinkname={$this->getKey()}&backlinkvalue={$this->id}'><img src='/images/lightning/new.png' border='0' /></a>" : '').($link_settings['edit_js'] ? " <a href='' onclick='{$link_settings['edit_js']}.newLink({$this->id})'>New</a>" : '')."</td></tr>"; // TODO changed from below: $row[$this->getKey()] to $this->id
                                for($i = 0; $i < count($link_settings['active_list']); $i++) {
                                    echo "<tr id='link_{$link}_{$link_settings['active_list'][$i][$link_settings['key']]}' {$style}>";
                                    foreach($link_settings['active_list'][$i] as $v) {
                                        echo "<td>{$v}</td>";
                                    }
                                    if ($link_settings['edit_link'] != '') {
                                        echo "<td><a href='{$link_settings['edit_link']}{$joinchar}action=edit&id={$link_settings['active_list'][$i][$link_settings['key']]}'>Edit</a> <a href='{$link_settings['edit_link']}{$joinchar}action=delete&id={$link_settings['active_list'][$i][$link_settings['key']]}'><img src='/images/lightning/remove.png' border='0' /></a></td>";
                                    }
                                    if ($link_settings['edit_js'] != '') {
                                        echo "<td><a href='' onclick='{$link_settings['edit_js']}.editLink({$link_settings['active_list'][$i][$link_settings['key']]})'><img src='/images/lightning/edit.png' border='0' /></a> <a href='' onclick='{$link_settings['edit_js']}.deleteLink({$link_settings['active_list'][$i][$link_settings['key']]})'><img src='/images/lightning/remove.png' border='0' /></a></td>";
                                    }
                                    echo "</tr>";
                                }
                */
                /* 	END TODO: This section should mirror the similar section from the LIST MODE below */
            } else {
                // DISPLAY NAME ON THE LEFT
                if (isset($link_settings['display_name'])) {
                    echo "<tr><td>{$link_settings['display_name']}</td><td>";
                } else {
                    echo "<tr><td>{$link}</td><td>";
                }

                // LOAD THE LINKED ROWS
                // The local key is the primary key column by default or another specified column.
                $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->getKey();
                // The value of the local key column.
                $local_id = ($this->table) ? $this->list[$local_key] : $this->id;

                // If there is a local key ID and no active list, load it.
                if ($local_id > 0 && !isset($link_settings['active_list'])) {
                    $link_settings['active_list'] = $this->load_all_active_list($link_settings, $local_id );
                } elseif (empty($local_id)) {
                    // If there is no local ID, this is probably a new item, so the list should be blank.
                    $link_settings['active_list'] = array();
                }

                $link_settings['row_count'] = count($link_settings['active_list']);

                // IN EDIT/NEW MODE, SHOW A FULL FORM
                if ($this->action == "edit" || $this->action == "new") {
                    // IN EDIT MODE WITH THE full_form OPTION, SHOW THE FORM WITH ADD/REMOVE LINKS
                    if (!empty($link_settings['full_form'])) {
                        // editable forms (1 to many)
                        echo $this->render_full_linked_table_editable($link_settings);
                    } else {
                        // drop down menu (many to many)
                        if (!empty($link_settings['type']) && $link_settings['type'] == 'image') {
                            echo $this->render_linked_table_editable_image($link_settings);
                        } else {
                            echo $this->render_linked_table_editable_select($link_settings);
                        }
                    }
                }

                // FULL FORM MODE INDICATES THAT THE LINKED TABLE IS A SUB TABLE OF THE MAIN TABLE - A 1(table) TO MANY (subtable) RELATIONSHIP
                // for view mode, if "display" is set, use the "display" template
                elseif ($this->action == "view" && is_array($link_settings['active_list'])) {
                    if (isset($link_settings['display'])) {
                        // IN VIEW MODE WITH THE full_form OPTION, JUST SHOW ALL THE DATA
                        // loop for each entry
                        foreach($link_settings['active_list'] as $l) {
                            // loop for each field
                            $display = $link_settings['display'];
                            foreach($l as $f=>$v) {
                                if (isset($link_settings['fields'][$f])) {
                                    if ($link_settings['fields'][$f]['field'] == '') $link_settings['fields'][$f]['field'] = $f;
                                    $display = str_replace('{'.$f.'}', $this->print_field_value($link_settings['fields'][$f], $l), $display);
                                }
                            }
                            echo $display;
                            echo $link_settings['seperator'];
                            // insert break here?
                        }
                        // THIS IS A MANY TO MANY RELATIONSHIP
                        // otherwise just list out all the fields
                    } elseif (!empty($link_settings['full_form']) && $link_settings['full_form'] === true) {
                        // full form view
                        foreach($link_settings['active_list'] as $l) {
                            echo "<div class='subtable'><table>";
                            // SHOW FORM FIELDS
                            foreach($link_settings['fields'] as $f=>&$s) {
                                $s['field'] = $f;
                                $s['form_field'] = "st_{$link}_{$f}_{$l[$link_settings['key']]}";
                                if ($this->which_field($s) == "display") {
                                    echo "<tr><td>{$s['display_name']}</td><td>";
                                    echo $this->print_field_value($s, $l);
                                }
                            }
                            // ADD REMOVE LINKS
                            echo "</table></div>";
                        }
                    } else {
                        // list
                        echo 2;
                    }
                    // LIST MODE
                } elseif ($this->action == "list") {

                }
            }
        }
    }



    // this renders all the linked items as full forms so they can be edited and new items can be added
    // this would imply to show only the links that are actively linked to this table item for editing
    // this is a 1 to many relationship. it will load all of the links made using load_all_active_list()
    // any link connected is "owned" by this table row and will be editable from this table in edit mode
    function render_full_linked_table_editable(&$link_settings) {
        $output = "<input type='hidden' name='delete_subtable_{$link_settings['table']}' id='delete_subtable_{$link_settings['table']}' />";
        $output .= "<input type='hidden' name='new_subtable_{$link_settings['table']}' id='new_subtable_{$link_settings['table']}' />";
        if (count($link_settings['active_list']) > 0)
            foreach($link_settings['active_list'] as $l) {
                $output .= "<div class='subtable' id='subtable_{$link_settings['table']}_{$l[$link_settings['key']]}'><table>";
                // SHOW FORM FIELDS
                foreach($link_settings['fields'] as $f=>&$s) {
                    $link_settings['fields'][$f]['field'] = $f;
                    $link_settings['fields'][$f]['form_field'] = "st_{$link_settings['table']}_{$f}_{$l[$link_settings['key']]}";
                    $output .= $this->render_form_row($s, $l);
                }
                // ADD REMOVE LINKS
                $output .= "</table>";
                $output .= "<span class='link' onclick='delete_subtable(this)'>{$link_settings['delete_name']}</span>";
                $output .= "</div>";
            }

        // ADD BLANK FORM FOR ADDING NEW LINK
        $output .= "<div class='subtable' id='subtable_{$link_settings['table']}__N_' style='display:none;'><table>";

        // SHOW FORM FIELDS
        foreach($link_settings['fields'] as $f=>&$s) {
            $link_settings['fields'][$f]['field'] = $f;
            $link_settings['fields'][$f]['form_field'] = "st_{$link_settings['table']}_{$f}__N_";
            $output .= $this->render_form_row($s,array());
        }

        // ADD REMOVE LINKS
        $output .= "</table>";
        $output .= "<span class='link' onclick='delete_subtable(this)'>{$link_settings['delete_name']}</span>";
        $output .= "</div>";

        // ADD NEW LINK
        $output .= "<span class='link' onclick='new_subtable(\"{$link_settings['table']}\")'>{$link_settings['add_name']}</span>";
        return $output;
    }

    /**
     * Renders an 'upload image' button and a list of selected current images.
     *
     * @param $link_settings
     *
     * @return string
     */
    protected function render_linked_table_editable_image(&$link_settings) {
        CKEditor::init(true);
        JS::startup('lightning.table.init()');
        JS::set('table.links.' . $link_settings['table'], $link_settings);
        $output = '<span class="button add_image" id="add_image_' . $link_settings['table'] . '">Add Image</span>';
        $output .= '<span class="linked_images" id="linked_images_' . $link_settings['table'] . '">';
        foreach ($link_settings['active_list'] as $image) {
            $output .= '<span class="selected_image_container">
                <input type="hidden" name="linked_images_' . $link_settings['table'] . '[]" value="' . $image['image'] . '">
                <span class="remove">X</span>
                <img src="' . $image['image'] . '"></span>';
        }
        $output .= '</span>';

        return $output;
    }

    /**
     * this renders a linked table showing a list of all available options, and a list of
     * all items that are already added to this table item
     * this is a many to many - where you can add any of the options from loadAllLinkOptions()
     * but you can't edit the actual content unless you go to the table page for that table
     *
     * @param $link_settings
     * @return string
     */
    protected function render_linked_table_editable_select(&$link_settings) {
        // show list of options to ad
        // IN REGULAR MODE IF edit_js? IS TURNED ON
        $output = '';
        if (!empty($link_settings['edit_js'])) {
            $output .= "<select name='{$link_settings['table']}_list' id='{$link_settings['table']}_list' ></select>";
            $output .= "<input type='button' name='add_{$link_settings['table']}_button' value='Add {$link_settings['table']}' id='add_{$link_settings['table']}_button' onclick='{$link_settings['edit_js']}.newLink(\"{$this->id}\")' />";

            //DEFAULT VIEW MODE
        } else {
            $this->loadAllLinkOptions($link_settings);
            $options = array('');
            foreach($link_settings['options'] as $l) {
                $key = !empty($link_settings['index_fkey']) ? $link_settings['index_fkey'] : $link_settings['key'];
                $options[$l[$key]] = $l[$link_settings['display_column']];
            }
            $output .= BasicHTML::select($link_settings['table'] . '_list', $options);
            $output .= "<input type='button' name='add_{$link_settings['table']}_button' value='Add {$link_settings['table']}' id='add_{$link_settings['table']}_button' onclick='lightning.table.addLink(\"{$link_settings['table']}\")' />";
        }

        if (!empty($link_settings['pop_add'])) {
            $location = !empty($link_settings['table_url']) ? $link_settings['table_url'] :  "/table?table=".$link_settings['table'];
            $output .= "<a onclick='lightning.table.newPop(\"{$location}\",\"{$link_settings['table']}\",\"{$link_settings['display_column']}\")'>Add New Item</a>";
        }

        // create the hidden array field
        $output .= "<input type='hidden' name='{$link_settings['table']}_input_array' id='{$link_settings['table']}_input_array' value='";
        foreach($link_settings['active_list'] as $init)
            $output .= $init[$link_settings['key']].",";
        $output .= "' /><br /><div id='{$link_settings['table']}_list_container'>";
        // create each item as a viewable deleteable box
        foreach($link_settings['active_list'] as $init) {
            $output .= "<div class='{$link_settings['table']}_box table_link_box_selected' id='{$link_settings['table']}_box_{$init[$link_settings['key']]}'>{$init[$link_settings['display_column']]}
						<a href='#' onclick='javascript:".(!empty($link_settings['edit_js']) ? $link_settings['edit_js'].'.deleteLink('.
                    $init[$link_settings['key']].')' : "lightning.table.removeLink(\"{$link_settings['table']}\",{$init[$link_settings['key']]})").";return false;'>X</a></div>";
        }
        $output .= "</div></td></tr>";
        return $output;
    }

    // this loads all links that are actively joined by a foreign key on the remote table
    // or by a link table in between. this is used for a one to many relationship, (1 table row to many links)
    function load_all_active_list(&$link_settings, $row_id) {
        $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->getKey();
        if (!empty($link_settings['index'])) {
            // many to many - there will be an index table linking the two tables together
            $table = array('from' => $link_settings['index']);
            if (!empty($link_settings['index_fkey'])) {
                $table['join'] = array('JOIN', $link_settings['table'], 'ON `' . $link_settings['index'] . '`.`' . $link_settings['key'] . '` = `' . $link_settings['table'] . '`.`' . $link_settings['index_fkey'] . '`');
            } else {
                $table['join'] = array('JOIN', $link_settings['table'], "USING (`$link_settings[key]`)");
            }
            return Database::getInstance()->selectAll(
                $table,
                array($link_settings['index'].'.'.$local_key => $row_id),
                array(),
                'ORDER BY ' . $link_settings['display_column']
            );
        } else {
            // 1 to many - each remote table will have a column linking it back to this table
            return Database::getInstance()->selectAll($link_settings['table'], array($local_key => $row_id));
        }
    }

    // this loads all possible optiosn for a link to be joined
    // used in a many to many
    protected function loadAllLinkOptions(&$link_settings) {
        $where = !empty($link_settings['accessControl']) ? $link_settings['accessControl'] : array();
        $link_settings['options'] = Database::getInstance()->selectAll($link_settings['table'], $where, array(), 'ORDER BY ' . $link_settings['display_column']);
    }

    /**
     * Render the table pagination links.
     *
     * @return string
     *   The rendered HTML output.
     */
    function pagination() {
        $output = '';
        $pages = ceil($this->listCount / $this->maxPerPage);
        if ($pages > 1) {
            $output .= '<ul class="pagination">';
            $output .= '<li class="arrow ' . ($this->page_number > 1 ? '' : 'unavailable') . '"><a href="' . $this->createUrl($this->action, 1) . '">&laquo; First</a></li>';
            for($i = max(1, $this->page_number - 10); $i <= min($pages, $this->page_number+10); $i++) {
                if ($this->page_number == $i) {
                    $output.= '<li class="current">' . $i . '</li>';
                } else {
                    $output.= "<li><a href='".$this->createUrl($this->action, $i)."'>{$i}</a></li>";
                }
            }
            $output .= '<li class="arrow ' . ($this->page_number == $pages ? 'unavailable' : '') . '"><a href="' . $this->createUrl($this->action, $pages) . '">Last &raquo;</a></li>';
            $output .= '</ul>';
        }
        return $output;
    }

    function set_preset($new_preset) {
        $this->preset = $new_preset;
    }

    protected function replaceURLVariables($string) {
        $string = str_replace('{ID}', $this->id, $string);
        return $string;
    }

    public function createUrl($action = '', $id = 0, $field = '', $other = array()) {
        $vars = array();
        if ($action == 'list') $vars['p'] = $id;
        if ($action != '') $vars['action'] = $action;
        if ($id > 0) $vars['id'] = $id;
        if ($this->table_url) $vars['table'] = $this->table;
        if (isset($this->parentLink)) $vars[$this->parentLink] = $this->parentId;
        if ($field != '') $vars['f'] = $field;
        if ($this->cur_subset && $this->cur_subset != $this->subset_default) $vars['ss'] = $this->cur_subset;
        if (count($this->additional_action_vars) > 0) {
            $vars = array_merge($this->additional_action_vars, $vars);
        }
        if (count($other) > 0) {
            $vars = array_merge($vars, $other);
        }

        // Search.
        $sort = array();
        if (is_array($this->sort_fields) && count($this->sort_fields) > 0) {
            $sort_fields = $this->sort_fields;
            if (!empty($other['sort'])) {
                foreach($other['sort'] as $f=>$d) {
                    switch($d) {
                        case 'A': $sort_fields[$f] = 'A'; break;
                        case 'D': $sort_fields[$f] = 'D'; break;
                        case 'X':
                            $sort_fields[$f] =
                                (!empty($this->sort_fields[$f]) && $this->sort_fields[$f] == 'A')
                                    ? 'D' : 'A';
                            break;
                    }
                }
            }
            foreach($sort_fields as $f=>$d) {
                $sort[] = ($d == 'D') ? $f . ':D' : $f;
            }
            $vars['sort']=implode(';', $sort);
        } elseif (!empty($other['sort'])) {
            $sort = array();
            foreach($other['sort'] as $f=>$d) {
                switch($d) {
                    case 'D': $sort[] = $f . ':D'; break;

                    case 'A':
                    default:	 $sort[] = $f; break;
                }
            }
            $vars['sort']=implode(';', $sort);
        }

        $query = $_GET;
        unset($query['request']);
        unset($query['id']);

        // Put it all together
        $vars = http_build_query($vars + $query);
        return $this->action_file . ($vars != '' ? ('?' . $vars) : '');
    }

    function load_template($file) {
        if ($file == '') return;
        $template = file_get_contents($file);
        $template = str_replace('{table_link_new}', $this->createUrl("new"), $template);
        $template = str_replace('{table_header}', $this->header, $template);
        $template = str_replace('{parentId}', intval($this->parentId), $template);
        $template = str_replace('{calendar_mode}', $this->calendar_mode, $template);

        // additional action vars
        foreach($this->additional_action_vars as $f=>$v)
            $template = str_replace('{'.$f.'}', $v, $template);

        return $template;
    }

    function template_item_vars($template, $id) {
        $template = str_replace('{table_link_edit}', $this->createUrl("edit", $id), $template);
        $template = str_replace('{table_link_delete}', $this->createUrl("delete", $id), $template);
        $template = str_replace('{table_link_view}', $this->createUrl("view", $id), $template);
        $template = str_replace('{key}', $id, $template);

        // look for linked file names
        preg_match_all('/\{table_link_file\.([a-z0-9_]+)\}/i', $template, $matches);
        for($i = 1; $i < count($matches); $i = $i+2) {
            $m = $matches[$i][0];
            if ($this->fields[$m]['type'] == 'file') {
                $template = str_replace('{table_link_file.'.$m.'}', $this->createUrl("file", $id, $m), $template);
            }
        }

        // if calendar
        if ($this->type=='calendar') {
            $prev_year = $next_year = $this->calendar_year;

            $prev_month = $this->calendar_month - 1;
            if ($prev_month < 1) { $prev_month += 12; $prev_year--;}

            $next_month = $this->calendar_month + 1;
            if ($next_month > 12) { $next_month -= 12; $next_year++;}

            $template = str_replace('{prev_month_link}', $this->createUrl('',0,'',array('month'=>$prev_month,'year'=>$prev_year)), $template);
            $template = str_replace('{next_month_link}', $this->createUrl('',0,'',array('month'=>$next_month,'year'=>$next_year)), $template);
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
            $fields = Database::getInstance()->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(Database::FETCH_ASSOC);
        } else {
            $fields = array();
        }

        $return_fields = array();
        foreach ($fields as $column => $field) {
            $column = !empty($field['Field']) ? $field['Field'] : $column;
            $return_fields[$column] = array();
            foreach ($field as $key => $value) {
                $return_fields[$column][strtolower($key)] = $value;
            }
        }

        $return_fields = array_replace_recursive($return_fields, $preset);
        //make sure there is a 'field' element and 'display_name' for each $field
        foreach ($return_fields as $f => &$field) {
            if (empty($field['display_name'])) {
                $field['display_name'] = ucwords(str_replace("_"," ", $f));
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
//        if (is_array($this->links)) {
//            foreach($this->links as $l=>&$s) {
//                // Add missing defaults.
//                $s += array(
//                    'display_name' => ucwords(str_replace("_"," ", $l)),
//                    'add_name' => 'Add Item',
//                    'delete_name' => 'Delete Item',
//                    'list' => false,
//                );
//            }
//        }

        return $return_fields;
    }

    function which_field(&$field) {
        switch($this->action) {
            case "new":
                if ($this->userInputNew($field)) {
                    return "edit";
                } elseif ($this->userDisplayNew($field)) {
                    return "display";
                } else {
                    return false;
                }
                break;
            case "edit":
                if ($this->userInputEdit($field)) {
                    return "edit";
                } elseif ($this->userDisplayEdit($field)) {
                    return "display";
                } else {
                    return false;
                }
                break;
            case "view":
                return $this->displayView($field) ? 'display' : false;
                break;
            case "list":
            default:
                return $this->displayList($field) ? 'display' : false;
                break;
        }
    }

    // is the field editable in these forms
    function userInputNew(&$field) {
        if (isset($field['render_'.$this->action.'_field']))
            return true;
        if ($field['type'] == "note")
            return true;
        if ($field['type'] == 'hidden' || (!empty($field['hidden']) && $field['hidden'] == 'true'))
            return false;
        if ($field['field'] == $this->getKey() && empty($field['editable']))
            return false;
        if ($field['field'] == $this->parentLink)
            return false;
        if (!empty($field['editable']) && $field['editable'] === false)
            return false;
        if (!empty($field['list_only']))
            return false;
        return true;
    }

    function userInputEdit(&$field) {
        if (isset($field['render_'.$this->action.'_field']))
            return true;
        if ($field['type'] == "note")
            return true;
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            return false;
        if ($field['field'] == $this->getKey())
            return false;
        if ($field['field'] == $this->parentLink)
            return false;
        if (isset($field['editable']) && $field['editable'] === false)
            return false;
        if (!empty($field['list_only']))
            return false;
        if (!empty($field['set_on_new']))
            return false;
        return true;
    }

    function userDisplayNew(&$field) {
        if (!empty($field['list_only']))
            return false;
        // TODO: This should be replaced by an overriding method in the child class.
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['display_new_value']) && is_callable($field['display_new_value']))
        )
            return true;
        if ($field['field'] == $this->parentLink)
            return false;
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            return false;
        return true;
    }

    function userDisplayEdit(&$field) {
        if (!empty($field['list_only']))
            return false;
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            return false;
        // TODO: This should be replaced by an overriding method in the child class.
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['display_edit_value']) && is_callable($field['display_edit_value']))
        )
            return true;
        if ($field['field'] == $this->parentLink)
            return false;
        return true;
    }

    function displayList(&$field) {
        // TODO: This should be replaced by an overriding method in the child class.
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['displayList_value']) && is_callable($field['displayList_value']))
        )
            return true;
        if ($field['field'] == $this->parentLink)
            return false;
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            return false;
        if (!empty($field['unlisted']))
            return false;
        return true;
    }

    function displayView(&$field) {
        if (
            (!empty($field['display_value']) && is_callable($field['display_value']))
            || (!empty($field['displayView_value']) && is_callable($field['displayView_value']))
        )
            return true;
        if ($field['type'] == "note" && $field['view'])
            return true;
        if ($field['field'] == $this->parentLink)
            return false;
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            return false;
        if (!empty($field['list_only']))
            return false;
        return true;
    }

    // sould we even consider the posted/function value on insert? -- implemented
    function get_value_on_new(&$field) {
        if (isset($field['insert_function']))
            return true;
        if (isset($field['submit_function']))
            return true;
        if (!empty($field['force_default_new']) || !empty($field['default']))
            return true;
        if (!empty($field['set_on_new']))
            return true;
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            return false;
        if (isset($field['editable']) && $field['editable'] === false)
            return false;
        if (!empty($field['list_only']))
            return false;
        return true;
    }

    // should we even consider the posted/function value on update? -- implemented
    function get_value_on_update(&$field) {
        if (isset($field['modified_function']))
            return true;
        if (isset($field['submit_function']))
            return true;
        if ((!empty($field['type']) && $field['type'] == 'hidden') || !empty($field['hidden']))
            return false;
        if ($field['field'] == $this->parentLink)
            return false;
        if (isset($field['editable']) && $field['editable'] === false)
            return false;
        if (!empty($field['list_only']))
            return false;
        if ($field['field'] == $this->getKey())
            return false;
        return true;
    }

    function update_accessTable() {
        if (isset($this->accessTable)) {
            $accessTable_values = $this->getFieldValues($this->fields, true);
            if (!empty($accessTable_values)) {
                Database::getInstance()->update($this->accessTable, $accessTable_values, array_merge($this->accessTableCondition, array($this->getKey() => $this->id)));
            }
        }
    }

    protected function getFieldValues(&$field_list, $accessTable=false) {
        $output = array();
        $dependenciesMet = true;
        foreach($field_list as $f => $field) {
            // check for settings that override user input
            if ($this->action == "insert" && !$this->get_value_on_new($field)) {
                continue;
            } elseif ($this->action == "update" && !$this->get_value_on_update($field)) {
                continue;
            }
            if ($field['type'] == 'note') {
                continue;
            }
            if (!empty($field['nocolumn'])) {
                continue;
            }

            if (!empty($field['table']) && $field['table'] == "access" && !$accessTable) {
                continue;
            } elseif (!isset($field['table']) && $accessTable) {
                continue;
            }

            unset($val);
            $sanitize = false;
            $html = false;
            $ignore = false;

            if (!isset($field['form_field'])) $field['form_field'] = $field['field'];
            // GET THE FIELD VALUE

            // OVERRIDES

            if (!empty($field['force_default_new']) && $this->action == "insert") {
                $val = $field['default'];
                // developer entered, could need sanitization
                $sanitize = true;
            } elseif ($this->parentLink == $field['field']) {
                // parent link
                $val = $this->parentId;
                // already sanitized, not needed
                // FUNCTIONS
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
                            // delete previous file
                            $this->get_row();

                            if ($field['type'] == 'file') {
                                $val = $this->saveFile($field, $_FILES[$field['field']]);
                            } else {
                                $val = $this->saveImage($field, $_FILES[$field['field']]);
                            }
                        } else {
                            $ignore = true;
                        }
                        break;
                    case 'date':
                        $val = Time::getDate($field['form_field'], !empty($field['allow_blank']));
                        break;
                    case 'time':
                        $val = Time::getTime($field['form_field'], !empty($field['allow_blank']));
                        break;
                    case 'datetime':
                        $val = Time::getDateTime($field['form_field'], !empty($field['allow_blank']));
                        break;
                    case 'checkbox':
                        $val = (integer) Request::get($field['form_field'], 'boolean');
                        break;
                    case 'checklist':
                        $vals = '';
                        $maxi = 0;
                        foreach($field['options'] as $i => $opt) {
                            if (is_array($opt)) {
                                $maxi = max($maxi, $opt[0]);
                            } else {
                                $maxi = max($maxi, $i);
                            }
                        }
                        for($i = 0; $i <= $maxi; $i++) {
                            $vals .= ($_POST[$field['form_field'].'_'.$i] == 1 || $_POST[$field['form_field'].'_'.$i] == "on") ? 1 : 0;
                        }
                        $val = bindec(strrev($vals));
                        break;
                    case 'bit':
                        $val = ['bit' => decbin(Request::get($field['form_field'], 'int'))];
                        break;
                    case 'html':
                        $val = Request::get($field['form_field'],
                            'html',
                            !empty($field['allowed_html']) ? $field['allowed_html'] : '',
                            !empty($field['allowed_css']) ? $field['allowed_css'] : '',
                            !empty($field['trusted']),
                            !empty($field['full_page'])
                        );
                        break;
                    case 'int':
                    case 'float':
                    case 'email':
                    case 'url':
                        $val = Request::post($field['form_field'], $field['type']);
                        break;
                    default:
                        // This will include 'url'
                        // TODO: this can be set to include the date types above also.
                        $val = Request::get($field['form_field'], $field['type']);
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
                $val = $this->input_sanitize($val, $html);
            }

            // If this value is required.
            if (!empty($field['required']) && empty($val)) {
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

    protected function saveFile($field, $file) {
        // copy the uploaded file to the right directory
        // needs some security checks -- IMPLEMENT FEATURE - what kind? make sure its not executable?
        $val = $this->get_new_file_loc($field['location']);
        move_uploaded_file($file['tmp_name'], $field['location'].$val);

        if (isset($field['extension'])) {
            $extention = preg_match("/\.[a-z1-3]+$/", $_FILES[$field['field']]['name'], $r);
            $string .= ", `{$field['extension']}` = '".strtolower($r[0])."'";
        }
        return $string;
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
            $field['images'] = array($field);
        }

        $new_image = '';
        foreach ($field['images'] as $image) {
            $image = array_replace($field, $image);

            // Get the output file location.
            $output_format = $this->getOutputFormat($image, $file);
            $new_image = $this->getNewImageLocation($image, $output_format);
            $output_location = $this->getOutputPath($image) . '/' . $new_image;

            if (!empty($image['original'])) {
                copy($file['tmp_name'], $output_location);
                continue;
            }

            if (!empty($image['image_preprocess']) && is_callable($image['image_preprocess'])) {
                $imageObj->source = $image['image_preprocess']($imageObj->source);
            }

            if (!$imageObj->source) {
                // The image failed to load.
                return false;
            }

            // Set the quality.
            $quality = !empty($image['quality']) ? $image['quality'] : 75;

            $imageObj->process($image);

            if (!empty($image['image_postprocess']) && is_callable($image['image_postprocess'])) {
                $imageObj->processed = $image['image_postprocess']($imageObj->processed);
            }

            $path = pathinfo($output_location);
            if (!file_exists($path['dirname'])) {
                mkdir($path['dirname'], 0777, true);
            }

            switch ($output_format) {
                case 'png':
                    $imageObj->writePNG($output_location);
                    break;
                case 'jpg':
                default:
                    $imageObj->writeJPG($output_location, $quality);
                    break;
            }
        }
        return $new_image;
    }

    function decode_bool_group($int) {
        return str_split(strrev(decbin($int)));
    }

    // get the int val of a specific bit - ie convert 1 (2nd col form right or 10) to 2
    // this way you can search for the 2nd bit column in a checlist with: "... AND col&".table::get_bit_int(2)." > 0"
    public static function get_bit_int($bit) {
        bindec("1".str_repeat("0", $bit));
    }

    function input_sanitize($val, $allow_html = false) {

        $val = stripslashes($val);

        if ($allow_html === true && $this->trusted) {
            $clean_html = Scrub::html($val, '', '', TRUE);
        }
        elseif ($allow_html === true) {
            $clean_html = Scrub::html($val);
        }
        elseif ($allow_html)
            $clean_html = Scrub::html($val, $allow_html);
        else
            $clean_html = Scrub::text($val);
        return $clean_html;
    }

    function encrypt($table, $column, $value) {
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
     * get_row() gets a single entry from the table based on $this->id

     * Constructs a database query based on the following class variables:
     * @param string $table->table			the table to query
     * @param string $table->key		the table name of the parent link (foreign key table)
     * @param string $table->id		the id (foreign key) of the parentLink with which to link

     * @return stores result in $list class variable (no actual return result from the method)

     */
    function get_row($force=true) {
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

        $where = array();
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
        if ($this->singularity) {
            $where[$this->singularity] = $this->singularityID;
        }
        $join = array();
        if ($this->accessTable) {
            if ($this->accessTableJoinOn) {
                $join_condition = "ON ".$this->accessTableJoinOn;
            } else {
                $join_condition = "ON ({$this->accessTable}.{$this->getKey()}={$this->table}.{$this->getKey()})";
            }
            $join[] = array('LEFT JOIN', $this->accessTable, $join_condition);
            $where .= " AND ".$this->accessTableCondition;
        }
        $where[$this->getKey()] = $this->id;
        if ($this->table) {
            $this->list = Database::getInstance()->selectRow(
                array(
                    'from' => $this->table,
                    'join' => $join,
                ),
                $where
            );
        }
    }


    /**
     * loadList() obtains all the rows from the table

     * Constructs a database query based on the following class variables:
     * @param string $table->table			the table to query
     * @param string $table->parentLink		the table name of the parent link (foreign key table)
     * @param string $table->parentId		the id (foreign key) of the parentLink with which to link
     * @param string $table->list_where		?
     * @param string $table->accessControl	?
     * @param string $table->sort			names of columns, separated by commas to sort by.

     * @return stores result in $list class variable (no actual return result from the method)

     */
    function loadList() {

        // check for required variables
        if ($this->table == '') {
            return;
        }

        // build WHERE qualification
        $where = [];
        $join = [];
        $fields = [];
        if ($this->parentLink && $this->parentId) {
            $where[$this->parentLink] = $this->parentId;
        }
        if (!empty($this->list_where)) {
            $where = array_merge($this->list_where, $where);
        }
        if (!empty($this->accessControl)) {
            $where = array_merge($this->accessControl, $where);
        }
        if ($this->action == "autocomplete" && $field = Request::post('field')) {
            $this->accessControl[$field] = array('LIKE', Request::post('st') . '%');
        }
        if ($this->accessTable) {
            if ($this->accessTableJoinOn) {
                $join_condition = "ON (".$this->accessTableJoinOn . ")";
            } else {
                $join_condition = "ON ({$this->accessTable}.{$this->getKey()}={$this->table}.{$this->getKey()})";
            }
            $join[] = array($this->accessTableSchema, $this->accessTable, $join_condition);
            if ($this->accessTableCondition) {
                $where = array_merge($this->accessTableCondition, $where);
            }
            if ($this->accessTableColumns) {
                $fields[] = [$this->accessTable => $this->accessTableColumns];
            } else {
                $fields[] = [$this->accessTable => ['*']];
            }
        }
        if ($this->cur_subset) {
            if ($this->subset[$this->cur_subset]) {
                $where = array_merge($this->subset[$this->cur_subset], $where);
            }
        }
        if ($this->action == 'list') {
            $this->additional_action_vars['ste'] = Request::get('ste');
            $where[] = Database::getMultiFieldSearch($this->search_fields, explode(' ', Request::get('ste')), $this->searchWildcard);
        }

        // get the page count
        $this->listCount = Database::getInstance()->count(
            array(
                'from' => $this->table,
                'join' => $join,
            ),
            $where
        );

        $start = (max(1, $this->page_number) - 1) * $this->maxPerPage;

        // validate the sort order
        $sort = !empty($this->sort) ? " ORDER BY " . $this->sort : '';

        if ($this->join_where) {
            $join[] = array('LEFT JOIN', $this->join_where['table']);
        }

        if ($this->action == "autocomplete") {
            $fields[] = array($this->getKey() => "`{$_POST['field']}`,`{$this->getKey()}`");
            $sort = "ORDER BY `{$_POST['field']}` ASC";
        } else {
            $fields[] = array($this->table => array('*'));
        }

        $this->list = Database::getInstance()->selectIndexed(
            array(
                'from' => $this->table,
                'join' => $join,
            ),
            $this->getKey(),
            $where,
            $fields,
            $sort . ' LIMIT ' . $start . ', ' . $this->maxPerPage
        );
    }

    function executeTask() {
        // do we load a subset or ss vars?
        if (isset($_REQUEST['ss'])) $this->cur_subset = Scrub::variable($_REQUEST['ss']);
        elseif ($this->subset_default) $this->cur_subset = $this->subset_default;

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
            switch($this->action_fields[$_GET['f']]['type']) {
                case "function":
                    $this->id = intval($_GET['id']);
                    $this->get_row();
                    $this->action_fields[$_GET['f']]['function']($this->list);
                    header("Location: ".$this->createUrl($_GET['ra'], $row[$this->getKey()]));
                    exit;
                    break;
            }
        }

        // check for a singularity, only allow edit/update (this means a user only has access to one of these entries, so there is no list view)
        if ($this->singularity) {
            $row = Database::getInstance()->selectRow($this->table, array($this->singularity => $this->singularityID));
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
        switch($this->action) {
            case "pop_return": break;
            case "autocomplete":
                $this->loadList();
                $output = Array("list"=>$this->list,"search"=>$_POST['st']);
                echo json_encode($output);
                exit;
                break;
            case "file":
                $this->loadMainFields();
                $field = $_GET['f'];
                $this->get_row();
                if ($this->fields[$field]['type'] == 'file' && count($this->list)>0) {
                    $file = $this->get_full_file_location($this->fields[$field]['location'], $this->list[$field]);
                    if (!file_exists($file)) die("No File Uploaded");
                    switch($this->list[$this->fields[$field]['extension']]) {
                        case '.pdf':
                            header("Content-Type: application/pdf"); break;
                        case '.jpg': case '.jpeg':
                        header("Content-Type: image/jpeg"); break;
                        case '.png':
                            header("Content-Type: image/png"); break;
                    }
                    readfile($file);
                } else die ('config error');
                exit;
            case "delete":
                if (!$this->deleteable) // FAILSAFE
                    break;
                if ($this->delconf)
                    break;
                $_POST['delconf'] = "Yes";
            case "delconf":
                if (!$this->deleteable) // FAILSAFE
                    break;
                if ($_POST['delconf'] == "Yes") {
                }
            case "list_action":
            case "list":
            case '':
            default:
                $this->action = "list";
                break;
        }
    }

    function check_default_rowClick() {
        if (!isset($this->rowClick) && $this->editable) {
            $this->rowClick = array('type' => 'action', 'action' => 'edit');
        }
    }

    function js_init_calendar() {
        $jsvars = array('action_file'=>$this->action_file);
        JS::inline('calendar_data=".json_encode($jsvars).";');
    }

    function js_init_data() {
        $table_data = array();
        if ($this->rowClick) {
            $table_data['rowClick'] = $this->rowClick;
            if (isset($this->table_url))
                $table_data['table'] = $this->table;
            if ($this->parentLink)
                $table_data['parentLink'] = $this->parentLink;
            if ($this->parentId)
                $table_data['parentId'] = $this->parentId;
            $table_data['action_file'] = $this->action_file;
            if (count($this->additional_action_vars) > 0)
                $table_data['vars'] = $this->additional_action_vars;
        }
        $js_startup = '';
        foreach($this->fields as $f => $field) {
            if (!empty($field['autocomplete'])) {
                $js_startup .= '$(".table_autocomplete").keyup(lightning.table.autocomplete);';
                $use_autocomplete = true;
            }
            if (!empty($field['default_reset'])) {
                $table_data['defaults'][$f] = $field['default'];
            }
            if (!empty($field['type']) && $field['type'] == "div") {
                $include_ck = true;
                $js_startup .= '$("#'.$f.'_div").attr("contentEditable", "true");
                table_div_editors["'.$f.'"]=CKEDITOR.inline("'.$f.'_div",CKEDITOR.config.toolbar_Full);';
            }
        }
        foreach($this->links as $link=>$link_settings) {
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
                $js_startup .= 'new_subtable("'.$link.'");';
            }
        }

        if (count($table_data) > 0 || !empty($use_autocomplete) || $js_startup) {
            if (count($table_data) > 0 || !empty($use_autocomplete)) {
                JS::inline('var table_data = ' . json_encode($table_data));
            }
            if ($js_startup) {
                JS::startup($js_startup);
            }
        }
    }

    function get_new_file_loc($dir) {
        // select random directory
        if (substr($dir,-1) == "/")
            $dir = substr($dir,0,-1);
        do{
            $rand_dir = "/".srand(microtime())."/".srand(microtime())."/";
            if (!file_exists($dir.$rand_dir))
                mkdir($dir.$rand_dir, 0755, true);
        } while (count(scandir($dir.$rand_dir))>1000);
        // create random file name
        do{
            $rand_file = sha1(srand(microtime()));
        } while(file_exists($dir.$rand_dir.$rand_file));

        // return only random dir and file
        return $rand_dir.$rand_file;

    }

    function get_full_file_location($dir, $file) {
        $f = $dir."/".$file;
        $f = str_replace("//","/", $f);
        $f = str_replace("//","/", $f);
        return $f;
    }

    function hasUploadfield() {
        foreach($this->fields as $f) {
            if ($f['type'] == 'file' || $f['type'] == 'image') {
                return true;
            }
        }
    }

    function set_posted_links() {
        foreach($this->links as $link => $link_settings) {
            // FOR 1 (local) TO MANY (foreign)
            if (!empty($link_settings['type']) && $link_settings['type'] == 'image') {
                $filenames = Request::post('linked_images_' . $link_settings['table'], 'array', 'string');
                // Insert new links.
                Database::getInstance()->insertMultiple($link_settings['table'],
                    array(
                        $link_settings['key'] => $this->id,
                        $link_settings['display_column'] => $filenames,
                    ),
                    true
                );
                // Remove old links.
                Database::getInstance()->delete($link_settings['table'],
                    array(
                        $link_settings['key'] => $this->id,
                        $link_settings['display_column'] => array('NOT IN', $filenames),
                    )
                );
            }
            elseif (!empty($link_settings['full_form'])) {
                if (!isset($this->list)) {
                    $this->get_row();
                }
                $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->getKey();
                $local_id = isset($this->list[$local_key]) ? $this->list[$local_key] : $this->id;

                if ($this->action == "update") {
                    // delete
                    $deleteable = preg_replace('/,$/', '', $_POST['delete_subtable_'.$link]);
                    if ($deleteable != '') {
                        Database::getInstance()->delete(
                            $link,
                            array($link_settings['key'] => array('IN', $deleteable), $local_key => $local_id)
                        );
                    }
                    // update
                    $list = Database::getInstance()->selectAll($link, array($local_key => $local_id), array());
                    foreach($list as $l) {
                        foreach($link_settings['fields'] as $f=>$field) {
                            $link_settings['fields'][$f]['field'] = $f;
                            $link_settings['fields'][$f]['form_field'] = "st_{$link}_{$f}_{$l[$link_settings['key']]}";
                        }
                        $field_values = $this->getFieldValues($link_settings['fields']);
                        Database::getInstance()->update($link, $field_values, array($local_key => $local_id, $link_settings['key'] => $l[$link_settings['key']]));
                    }
                }
                // insert new
                $new_subtables = explode(",", $_POST['new_subtable_'.$link]);
                foreach($new_subtables as $i) if ($i != '') {
                    foreach($link_settings['fields'] as $f=>$field) {
                        $link_settings['fields'][$f]['field'] = $f;
                        $link_settings['fields'][$f]['form_field'] = "st_{$link}_{$f}_-{$i}";
                    }
                    $field_values = $this->getFieldValues($link_settings['fields']);
                    Database::getInstance()->insert($link, $field_values, array($local_key => $local_id));
                }
            }
            elseif ($link_settings['index']) {
                // CLEAR OUT OLD SETTINGS
                Database::getInstance()->delete(
                    $link_settings['index'],
                    array($this->getKey() => $this->id)
                );

                // GET INPUT ARRAY
                $list = Request::get($link.'_input_array', 'explode', 'int');
                foreach($list as $l) {
                    Database::getInstance()->insert(
                        $link_settings['index'],
                        array(
                            $this->getKey() => $this->id,
                            $link_settings['key'] => $l,
                        )
                    );
                }
            }
        }
    }

    // print field or print editable field
    function print_field_value($field, &$row = null) {
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

        if (!empty($field['render_'.$this->action.'_field']) && is_callable($field['render_'.$this->action.'_field'])) {
            return $field['render_'.$this->action.'_field']($row);
        } elseif (!empty($field['display_value']) && is_callable($field['display_value'])) {
            return $field['display_value']($row);
        } else {
            switch(preg_replace('/\([0-9]+\)/', '', $field['type'])) {
                case 'lookup':
                    // a lookup will translate to a value drawn from the lookup table based on the key value
                    if ($field['lookuptable'] && $field['display_column']) {
                        if ($v) {
                            $fk = isset($field['lookupkey']) ? $field['lookupkey'] : $field['field'];
                            $filter = array($fk => $v);
                            if (!empty($field['filter'])) {
                                $filter += $field['filter'];
                            }
                            // TODO: implement a cache or join in the main query to prevent multiple query execution.
                            $value = Database::getInstance()->selectRow(
                                $field['lookuptable'],
                                $filter,
                                array(
                                    $field['display_column'], $fk
                                )
                            );
                            return $value[$field['display_column']];
                        }
                    } else {
                        return $v;
                    }
                    break;
                case 'yesno':
                    $field['options'] = Array(1=>'No',2=>'Yes');
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
                            return substr($v,0,64)."...";
                        else
                            return $v;
                    }
                    else // edit should show full text
                        return $v;
                    break;
                case 'time':
                    return Time::printTime($v);
                    break;
                case 'date':
                    return Time::printDate($v);
                    break;
                case 'datetime':
                    return Time::printDateTime($v);
                    break;
                case 'checkbox':
                    return "<input type='checkbox' disabled ".(($v==1)?"checked":'')." />";
                    break;
                case 'checklist':
                    $vals = $this->decode_bool_group($v);
                    $output = '';
                    foreach($field['options'] as $i => $opt) {
                        if (is_array($opt)) {
                            $id = $opt[0];
                            $name = $opt[1];
                        } else {
                            $id = $i;
                            $name = $opt;
                        }
                        $output .= "<div class='checlist_item'><input type='checkbox' disabled ".(($vals[$id]==1)?"checked":'')." />{$name}</div>";
                    }
                    return $output;
                    break;
                case 'note':
                    return $field['note'];
                    break;
                default:
                    return $this->convert_quotes($v);
                    break;

            }
        }
    }

    function convert_quotes($v) {
        return str_replace("'", "&apos;", str_replace('"',"&quot;", $v));
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
        return rand(0,99999) . '.' . $extension;
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
        $uploadedFormat = $this->getUploadedFileFormat($file);

        if (empty($field['format'])) {
            return $uploadedFormat;
        }
        if (is_array($field['format'])) {
            return in_array($uploadedFormat, $field['format']) ? $uploadedFormat : $field['format'][0];
        }
        return $field['format'];
    }

    /**
     * Get the image location for a file name.
     *
     * @param array $field
     *   The field settings.
     * @param string $output_format
     *   The file from the $_FILE array.
     *
     * @return string
     *   The new absolute file location.
     */
    protected function getNewImageLocation($field, $output_format) {
        if (!empty($field['file_name'])) {
            return $field['file_name'];
        }
        $base = $this->getOutputPath($field);
        do {
            $file = $this->getNewRandomImageName($output_format);
        } while (file_exists($base . '/' . $file));
        return $file;
    }

    protected function getOutputPath($field) {
        if (empty($field['location'])) {
            return HOME_PATH;
        } elseif (strpos($field['location'], '/') !== 0) {
            return HOME_PATH . '/' . $field['location'];
        } else {
            return $field['location'];
        }
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
        return $field['weblocation'] . '/' . $file;
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
    protected function renderEditField($field, &$row = array()) {
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

        if (isset($this->preset[$field['field']]['render_'.$this->action.'_field'])) {
            $this->get_row(false);
            return $this->preset[$field['field']]['render_'.$this->action.'_field']($this->list);
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
        $options = array();
        $return = '';
        switch(preg_replace('/\([0-9]+\)/', '', $field['type'])) {
            case 'text':
            case 'mediumtext':
            case 'longtext':
            case 'html':
                $config = array();
                $editor = (!empty($field['editor'])) ? strtolower($field['editor']) : 'default';
                switch($editor) {
                    case 'full':		$config['toolbar']="CKEDITOR.config.toolbar_Full";        break;
                    case 'print':		$config['toolbar']="CKEDITOR.config.toolbar_Print";       break;
                    case 'basic_image':	$config['toolbar']="CKEDITOR.config.toolbar_Basic_Image"; break;
                    case 'basic':
                    default:			$config['toolbar']="CKEDITOR.config.toolbar_Basic";       break;
                }
                if (!empty($field['full_page'])) {
                    $config['fullPage'] = true;
                    $config['allowedContent'] = true;
                }

                if (!empty($field['height'])) {
                    $config['height'] = $field['height'];
                }
                if (!empty($field['upload'])) {
                    $config['finder'] = true;
                }
                return CKEditor::iframe($field['form_field'], $field['Value'], $config);
                break;
            case 'div':
                if ($field['Value'] == '')
                    $field['Value'] = "<p></p>";
                return "<input type='hidden' name='{$field['form_field']}' id='{$field['form_field']}' value='".$this->convert_quotes($field['Value'])."' />
							<div id='{$field['form_field']}_div' spellcheck='true'>{$field['Value']}</div>";
                break;
            case 'plaintext':
                return "<textarea name='{$field['form_field']}' id='{$field['form_field']}' spellcheck='true' cols='90' rows='10'>{$field['Value']}</textarea>";
                break;
            case 'hidden':
                return "<input type='hidden' name='{$field['form_field']}' id='{$field['form_field']}' value='".$this->convert_quotes($field['Value'])."' />";
                break;
            case 'image':
                if (!empty($field['Value'])) {
                    $return .= '<img src="' . $this->getImageLocationWeb($field, $field['Value']) . '" class="table_edit_image" />';
                }
                // Fall through.
            case 'file':
                if (($field['Value'] != '' && (!isset($field['replaceable']) || empty($field['replaceable']))) || $field['Value'] == '') {
                    $return .= "<input type='file' name='{$field['form_field']}' id='{$field['form_field']}' />";
                }
                return $return;
                break;
            case 'time':
                return Time::timePop($field['form_field'], $field['Value'], !empty($field['allow_blank']));
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
                return Time::dateTimePop($field['form_field'], $field['Value'], !empty($field['allow_blank']), isset($field['start_year']) ? $field['start_year'] : date('Y') - 10);
                break;
            case 'lookup':
            case 'yesno':
            case 'state':
            case 'country':
            case 'select':
                if ($field['type'] == 'lookup') {
                    $options = Database::getInstance()->selectColumn(
                        $field['lookuptable'],
                        $field['display_column'],
                        !empty($field['filter']) ? $field['filter'] : array(),
                        !empty($field['lookupkey']) ? $field['lookupkey'] : $field['field']
                    );
                }
                elseif ($field['type'] == "yesno")
                    $options = Array(1=>'No', 2=>'Yes');
                elseif ($field['type'] == "state")
                    $options = Location::getStateOptions();
                elseif ($field['type'] == "country")
                    $options = Location::getCountryOptions();
                else
                    $options = $field['options'];
                if (!is_array($options)) {
                    return false;
                }

                if (!empty($field['allow_blank'])) {
                    $options = array('' => '') + $options;
                }
                $output = BasicHTML::select($field['form_field'], $options, $field['Value']);

                if (!empty($field['pop_add'])) {
                    if ($field['table_url']) $location = $field['table_url'];
                    else $location = "table.php?table=".$field['lookuptable'];
                    $output .= "<a onclick='lightning.table.newPop(\"{$location}\",\"{$field['form_field']}\",\"{$field['display_column']}\")'>Add New Item</a>";
                }
                return $output;
                break;
            case 'range':
                $output = "<select name='{$field['form_field']}' id='{$field['form_field']}'>";
                if ($field['allow_blank'])
                    $output .= '<option value="0"></option>';
                if ($field['start'] < $field['end']) {
                    for($k = $field['start']; $k <= $field['end']; $k++)
                        $output .= "<option value='{$k}'".(($field['Value'] == $k) ? 'selected="selected"' : '').">{$k}</option>";
                }
                $output .= '</select>';
                return $output;
                break;
            case 'checkbox':
                return "<input type='checkbox' name='{$field['form_field']}' id='{$field['form_field']}' value='1' ".(($field['Value']==1)?"checked":'')." />";
                break;
            case 'note':
                return $field['note'];
                break;
            case 'checklist':
                $vals = $this->decode_bool_group($field['Value']);
                $output = '';
                foreach($field['options'] as $i => $opt) {
                    if (is_array($opt)) {
                        $id = $opt[0];
                        $name = $opt[1];
                    } else {
                        $id = $i;
                        $name = $opt;
                    }
                    $output .= "<div class='checlist_item'><input type='checkbox' name='{$field['form_field']}_{$id}' value='1' ".(($vals[$id]==1)?"checked":'')." />{$name}</div>";
                }
                return $output;
                break;
            case 'varchar':
            case 'char':
                preg_match('/(.+)\(([0-9]+)\)/i', $field['type'], $array);
                $options['size'] = $array[2];
            default:
                if (!empty($field['autocomplete'])) {
                    $options['classes'] = array('table_autocomplete');
                    $options['autocomplete'] = false;
                }
                return Text::textfield($field['form_field'], $field['Value'], $options);
                break;
        }
    }
}