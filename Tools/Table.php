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
	$myTableObject->key = "changeme_table_primary_index";
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
 	$myTableObject->preset['changeme_table_foreign_key'] = array('Type'=>'lookup', 'lookuptable'=>'changeme_related_table_name', 'display_name'=>'changeme_display_column_from_related_table');

	// set this to true to allow for serial updates
	$myTableObject->enable_serial_update = 1;

*/

namespace Lightning\Tools;

use Lightning\View\Field;
use Lightning\View\JS;

class Table{

    var $table;
    var $action;
    var $function;
    var $id = 0;
    var $list;
    var $template_vars;	//used when you want to set a value in the header (array)
    var $preset = array();
    var $key;
    var $delconf = true;
    var $action_file;
    var $default_action;
    var $fields = array();
    var $links = array();
    var $styles = array();
    var $sort;
    var $max_per_page = 25;
    var $list_count = 0;
    var $page = 1;
    var $action_fields=array();
    var $custom_templates=array();
    var $list_where;
    var $access_control;
    // This allows the edit form to return to a page other than the list view
    var $refer_return;
    // Set to true to allow for serial update
    var $enable_serial_update;
    // Set to true to automatically enter update mode on the next record when saving the current record.
    var $serial_update;
    var $custom_template_directory = "table_templates/";
    var $editable = true;
    var $deleteable = true;
    var $addable = true;
    var $cancel = false;
    var $searchable = false;
    var $sortable = false;
    var $new_td_key = "";	//when generating a table (i.e. calendar table) this key tells us what field is the trigger for creating a new TD
    var $calendar_month = 1;
    var $calendar_year = '';
    var $subset = Array();
    var $search_fields = array();
    var $local_field_list = false;
    var $submit_redirect = true;
    var $additional_action_vars = array();
    var $button_names = Array("insert"=>"Insert","cancel"=>"Cancel","update"=>"Update");
    var $action_after = Array("insert"=>"list","update"=>"list");
    var $function_after = Array();
    var $table_descriptions = "table_descriptions/";

    function __construct($options = array()){
        $this->calendar_year = date('Y');
        $this->calendar_month = date('m');
        if(isset($_REQUEST['action'])) $this->action = $_REQUEST['action'];
        if($_REQUEST['pf']){
            if(!isset($this->action))
                $this->action = "new";
            $this->additional_action_vars['pf'] = $_REQUEST['pf'];		//Per DAB: PF = popup field
            $this->additional_action_vars['pfdf'] = $_REQUEST['pfdf'];	//Per DAB: PFDF = popup display field
        }
        if ($this->action == 'new') {
            $backlinkname = '';
            $backlinkvalue = '';
            // check for a backlink to be prepopulated in a new entry
            if (isset($_REQUEST['backlinkname'])) $backlinkname = $_REQUEST['backlinkname'];
            if (isset($_REQUEST['backlinkvalue'])) $backlinkvalue = $_REQUEST['backlinkvalue'];
            // must have both
            if ($backlinkname && $backlinkvalue) {
                $this->preset[$backlinkname] = array('Default' => $backlinkvalue);
            }
        }
        if(isset($_POST['function'])) $this->function = $_POST['function'];
        if(isset($_REQUEST['id'])) $this->id = intval($_REQUEST['id']);
        if(isset($_REQUEST['p'])) $this->page = max(1,intval($_REQUEST['p']));
        if(isset($_REQUEST['serialupdate'])) $this->serial_update = $_REQUEST['serialupdate'];
        if(isset($_REQUEST['refer_return'])) $this->refer_return = $_REQUEST['refer_return'];

        // load the sort fields
        if($_REQUEST['sort']){
            $field = explode(";", $_REQUEST['sort']);
            $this->sort_fields = array();
            $sort_strings = array();
            foreach($field as $f){
                $f = explode(":", $f);
                if($f[1] == "D"){
                    $this->sort_fields[$f[0]] = "D";
                    $sort_strings[] = "`{$f[0]}` DESC";
                } else {
                    $this->sort_fields[$f[0]] = "A";
                    $sort_strings[] = "`{$f[0]}` ASC";
                }
            }
            $this->sort = implode(",", $sort_strings);
        }

        $this->action_file = preg_replace('/\?.*/','',$_SERVER['REQUEST_URI']);

        foreach ($options as $name => $value) {
            $this->$name = $value;
        }
    }

    function set_read_only ($read_only = true){
        if($read_only){
            $this->editable = false;
            $this->deleteable = false;
            $this->addable = false;
        }
    }

    function set_var($var, $val){
        $this->$var = $val;

    }

    function load_table_description($table){
        $this->table = $table;
        include $this->table_descriptions.$table.'.php';
    }

    function render_table(){
        global $errors;

        $this->get_fields();
        $this->check_default_row_click();
        $this->render_header();

        if($this->action == "new" && !$this->addable){
            $errors[] = "Access Denied";
            return false;
        }
        if($this->action == "edit" && !$this->editable){
            $errors[] = "Access Denied";
            return false;
        }

        switch($this->action){
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
                if(!$this->deleteable){
                    $errors[] = "Access Denied";
                    break; // FAILSAFE
                }
                echo $this->render_del_conf();
                break;
            case 'list':
            default:
                if($this->searchable)
                    $this->search_form();
                $this->get_list();
                $this->render_action_header();
                echo $this->render_list();
                break;

        }
        // we have to call this last because certain information is learned
        // during the render process
        $this->js_init_data();
    }

    function search_form(){
        // @todo namespace this
        JS::inline('table_search_i=0;table_search_d=0;');
        return 'Search: <input type="text" name="table_search" value="" onkeyup="table_search(this);" />';
    }

    function render_pop_return(){
        $this->get_row();
        $send_data = array("pf"=>$this->additional_action_vars['pf'], 'id'=>$this->id, 'pfdf'=>$this->list[$this->additional_action_vars['pfdf']]);
        JS::startup('update_parent_pop('.json_encode($send_data).')');
    }

    function render_calendar_list(){
        $this->js_init_calendar();
        $this->get_fields();
        $this->render_header();
        $last_date = 0;
        foreach($this->list as $l){
            if($last_date != $l['date']){
                $last_date = $l['date'];
                echo "<div class='list_date_header'>".$this->print_nice_date($l['date'])."</div>";
            }
            echo "<div class='list_cal_item' class='office_calendar {item_color}'>";
            if($l['evt_type'] == 1){
                $this_item_output = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_t']);
            }elseif($l['evt_type'] == 2){
                $this_item_output = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_d']);
            } else {
                $this_item_output = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_c']);
            }
            foreach($this->fields as $field) {
                $this_item_output = str_replace('{'.$field['Field'].'}', $this->print_field_value($field, $l), $this_item_output);
            }
            echo $this_item_output;
            echo "</div>";
        }
    }

    function print_nice_date($jd){
        // format of Thursday, Septemeber 22nd 2012
        if($jd == 0) return "";
        $date = explode("/",JDToGregorian($jd));
        $output = jddayofweek($jd,1).", ".jdmonthname($jd,3)." {$date[1]}, {$date[2]}";
        return $output;
    }

    function render_calendar_table($get_new_list = true) {
        $this->js_init_calendar();
        if($get_new_list)
            $this->get_list();
        $this->get_fields();
        $this->render_header();
        $today = GregorianToJD(date("m"), date("d"), date("Y"));


        // create index for fast access
        $date_index = array();
        foreach($this->list as $li){
            if(!is_array($date_index[$li['date']]))
                $date_index[$li['date']] = Array();
            $date_index[$li['date']][] = $li;
        }

        if(isset($this->custom_templates[$this->action.'_item_t'])){
            $calendar_month = $this->calendar_month;
            $calendar_year = $this->calendar_year;
            /*
                        echo "<h2>";
                        echo date("F", mktime(0, 0, 0, $calendar_month, 1, $calendar_year));
                        echo " " . date("Y", mktime(0, 0, 0, $calendar_month, 1, $calendar_year));
                        echo "</h2>\n";
            */
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
                $today_class = ($today == GregorianToJD($calendar_month, $i, $calendar_year)) ? 'today' : "";

                echo "\n<td class='actv {$today_class}'><p class='date'>$i";
                if($this->editable)
                    echo "<a href='".$this->create_url("new",0,'',array("date"=>gregoriantojd($calendar_month, $i, $calendar_year))).
                        "' /><img src='/images/main/new.png' border='0' /></a>";
                echo "</p><div class='events'>"
                    . $this->render_calendar_items($date_index[GregorianToJD($calendar_month, $i, $calendar_year)])
                    . "</div></td>";
                if ($day_in_week == 0) {
                    echo "\n</tr>\n<tr>";
                }

                $i++;
            }
            echo "\n</tr>\n</table>";


            /*$prev_td_val = "";
            foreach($this->list as $key=>$row){
                if ($prev_td_val == "")
                    $prev_td_val = $row[$this->new_td_key];
                else {


                }
                // create temp instance for this row
                $this_item_output = $template;
                // replace variables
                foreach($this->fields as $field){
                    $this_item_output = str_replace('{'.$field['Field'].'}', $this->print_field_value($field, $row), $this_item_output);
                }
                // replace functional links
                $this_item_output = $this->template_item_vars($this_item_output,$row[$this->key]);
                echo $this_item_output;
            }*/


        }

    }

    function render_calendar_items(&$list) {
        $template_t = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_t']);
        $template_c = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_c']);
        $template_d = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item_d']);
        $ret_str = "";

        if(!is_array($list)) return;
        foreach($list as $row) {
            // load the template
            if($row['evt_type'] == 1)
                $this_item_output = $template_t;
            elseif($row['evt_type'] == 2)
                $this_item_output = $template_d;
            else
                $this_item_output = $template_c;

            // replace variables
            foreach($this->fields as $field) {
                $this_item_output = str_replace('{'.$field['Field'].'}', $this->print_field_value($field, $row), $this_item_output);
            }
            $this_item_output = $this->template_item_vars($this_item_output,$row[$this->key]);
            $ret_str .= $this_item_output;
        }
        return $ret_str;
    }

    function render_del_conf(){
        // get delete confirmation
        $output = "<br /><br />Are you sure you want to delete this?<br /><br /><form action='".$this->create_url()."' method='POST'>";
        $output .= "<input type='hidden' name='id' value='{$this->id}' /><input type='hidden' name='action' value='delconf' />";
        $output .= "<input type='submit' name='delconf' value='Yes'/><input type='submit' name='delconf' value='No'/>";
        if ($this->refer_return) {
            $output .= '<input type="hidden" name="refer_return" value="'.$this->refer_return.'" />';
        }
        $output .= "</form>";
        return $output;
    }

    function render_header(){


        if(is_array($this->template_vars))
            foreach ($this->template_vars as $row) {

            }

        if(isset($this->custom_templates[$this->action.'_header'])) {

            $template = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_header']);
            $template = $this->template_item_vars($template,$this->id);
        }
        else if($this->header != "") {
            $template = "<h1>{$this->header}</h1>";
        }

        if(is_array($this->template_vars))
            foreach ($this->template_vars as $key=>$val) {
                $template = str_replace('{'.$key.'}', $val, $template);

            }
        echo $template;
    }

    function render_action_header(){
        if(isset($this->custom_templates[$this->action.'_action_header'])){
            if($this->custom_templates[$this->action.'_action_header'] != "")
                echo $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_action_header']);
        } elseif($this->addable)
            echo "<a href='".$this->create_url("new")."'><img src='/images/main/new.png' border='0' /></a><br />";
    }

    function render_list(){

        if(count($this->list) == 0){

            echo "<p></p><p></p><p>There is nothing to show. <a href='".$this->create_url('new')."'>Add a new entry</a></p><p></p><p></p>";

        }

        $output = "";
        if(isset($this->custom_templates[$this->action.'_item'])){
            // load the template
            $template = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item']);
            foreach($this->list as $row){
                // create temp instance for this row
                $this_item_output = $template;
                // replace variables
                foreach($this->fields as $field){
                    if($this->which_field($field)){
                        $this_item_output = str_replace('{'.$field['Field'].'}', $this->print_field_value($field, $row), $this_item_output);
                    }
                }
                // replace functional links
                $this_item_output = $this->template_item_vars($this_item_output,$row[$this->key]);
                $output .= $this_item_output;
            }
            return $output;
        } else {
            // show pagination
            $output .= $this->pagination();
            // if there is something to list
            if(count($this->list) > 0){

                // add form if required
                if($this->action_fields_requires_submit())
                    $output.= "<form action='".$this->create_url()."' method='POST'>"; // {$this->action_file}
                $output.= "<div id='list_table_container'>";
                $output.= "<table cellspacing='0' cellpadding='3' border='0'>";
                // SHOW HEADER
                $output.= "<tr>";

                if(is_array($this->field_order)){
                    foreach($this->field_order as $f){
                        if(isset($this->fields[$f])){
                            if($this->which_field($this->fields[$f])){
                                if($this->sortable)
                                    $output .= "<td><a href='".$this->create_url('list',0,'',array('sort'=>array($f=>'X')))."'>{$this->fields[$f]['display_name']}</a></td>";
                                else
                                    $output.= "<td>{$this->fields[$f]['display_name']}</td>";
                            }
                        } elseif(isset($this->links[$f])) {
                            if($this->links[$f]['list']===true)
                                if($this->links[$f]['display_name']) $output.= "<td>{$this->links[$f]['display_name']}</td>";
                                else $output.= "<td>{$f}</td>";
                        }
                    }
                } else {
                    foreach($this->fields as $f=>&$field){
                        if($this->which_field($field)){
                            if($this->sortable)
                                $output .= "<td><a href='".$this->create_url('list',0,'',array('sort'=>array($f=>'X')))."'>{$field['display_name']}</a></td>";
                            else
                                $output.= "<td>{$field['display_name']}</td>";
                        }
                    }
                    foreach($this->links as $l=>$v)
                        if($v['list']===true)
                            if($v['display_name']) $output.= "<td>{$v['display_name']}</td>";
                            else $output.= "<td>{$l}</td>";
                }

                // SHOW ACTION HEADER

                $output.= $this->render_action_fields_headers();
                $output.= "</tr>";


                // loop through DATA rows
                $dark = true;
                foreach($this->list as $row){
                    // alternate light and dark rows
                    $style = $dark ? 'class="dark_row"' : 'class="light_row"';
                    $dark = $dark ? false : true;
                    // prepare click action for each row
                    if($this->row_click){
                        switch($this->row_click['type']){
                            case 'url':
                            case 'action':
                                $click = "onclick='table_click({$row[$this->key]})'";
                                break;
                        }
                    }
                    $output.= "<tr {$style} {$click}>";
                    // SHOW FIELDS AND VALUES
                    foreach($this->fields as &$field){
                        if($this->which_field($field)){
                            if($field['align'] != "")
                                $output.= "<td align='{$field['align']}'>";
                            else
                                $output.= "<td>";
                            if($field['list_html'])
                                $output.= $this->print_field_value($field,$row);
                            else
                                $output.= strip_tags($this->print_field_value($field,$row));
                            $output.= "</td>";
                        }
                    }
                    // LINKS w ALL ITEMS LISTED IN ONE BOX
                    $output.= $this->render_linked_list($row);

                    // EDIT, DELETE, AND OTHER ACTIONS
                    $output.= $this->render_action_fields_list($row);

                    // CLOSE MAIN DATA ROW
                    $output.= "</tr>";


                    // LINKS EACH ITEM GETS ITS OWN ROW
                    $output.= $this->render_linked_table($row);
                }
                $output.= "</table></div>";
                if($this->action_fields_requires_submit())
                    $output.= "<input type='submit' name='submit' value='Submit' /></form>";
                $output.= $this->pagination();
            }
            return $output;
        }
    }

    function action_fields_requires_submit(){
        foreach($this->action_fields as $a=>$action){
            if($action['type'] == "checkbox") return true;
        }
    }

    // caled when rendering lists
    function render_linked_list(&$row){
        $output="";
        foreach($this->links as $link => $link_settings){
            if($link_settings['list'] === true){
                if($link_settings['index']!="")
                    $links = Database::getInstance()->assoc("SELECT * FROM `{$link_settings['index']}` JOIN `{$link}` USING (`{$link_settings['key']}`) WHERE `{$this->key}` = '{$row[$this->key]}'");
                else
                    $links = Database::getInstance()->assoc("SELECT * FROM `{$link}` WHERE `{$this->key}` = '{$row[$this->key]}'");

                $output.= "<td>";
                $displays = array();
                if(isset($link_settings["display"])){
                    foreach($links as $l)
                        if(is_array($link_settings['fields'])){
                            $display = $link_settings["display"];
                            foreach($link_settings['fields'] as $f=>$a){
                                if(!isset($a['Field'])) $a['Field'] = $f;
                                $display = str_replace('{'.$f.'}', $this->print_field_value($a,$l), $display);
                            }
                            $displays[] = $display;
                        } else
                            $displays[] = $l[$link_settings['display_column']];
                    if(!isset($link_settings['seperator']))
                        $link_settings['seperator'] = ", ";
                    $output.= implode($link_settings['seperator'], $displays);
                } else {
                    $output .= "table";
                }
                $output.= "</td>";
            }
        }
        return $output;
    }


    // called when rendering lists
    function render_linked_table(&$row){
        $output = "";
        foreach($this->links as $link => $link_settings){
            if($link_settings['list'] === "each"){
                $this->load_all_active_list($link_settings, $row[$this->key] );

                // Set the character to join the URL parameters to the edit_link
                $joinchar = (strpos($link_settings['edit_link'], "?") !== false) ? '&' : '?';

                if($link_settings['display_header'])
                    $output.= "<tr {$style}><td>{$link_settings['display_header']}";
                if($link_settings['edit_link'])
                    $output .= " <a href='{$link_settings['edit_link']}{$joinchar}action=new&backlinkname={$this->key}&backlinkvalue={$row[$this->key]}'>New</a>";
                if($link_settings['edit_js'])
                    $output .= " <a href='' onclick='{$link_settings['edit_js']}.newLink({$row[$this->key]})'>New</a>";
                $output .= "</td></tr>";
                for($i = 0; $i < count($links); $i++){
                    $output.= "<tr id='link_{$link}_{$links[$i][$link_settings['key']]}' {$style}>";
                    foreach($links[$i] as $v){
                        $output.= "<td>{$v}</td>";
                    }
                    if($link_settings['edit_link'] != ''){
                        $output.= "<td><a href='{$link_settings['edit_link']}{$joinchar}action=edit&id={$links[$i][$link_settings['key']]}'>Edit</a> <a href='{$link_settings['edit_link']}{$joinchar}action=delete&id={$links[$i][$link_settings['key']]}'><img src='/images/main/remove.png' border='0' /></a></td>";
                    }
                    if($link_settings['edit_js'] != ''){
                        $output.= "<td><a href='' onclick='{$link_settings['edit_js']}.editLink({$links[$i][$link_settings['key']]})'>Edit</a> <a href='' onclick='{$link_settings['edit_js']}.deleteLink({$links[$i][$link_settings['key']]})'><img src='/images/main/remove.png' border='0' /></a></td>";
                    }
                    $output.= "</tr>";
                }
                $output.= "<tr {$style}><td colspan='20' bgcolor='#000'></td></tr>"; // What is this used for? -ctg 6.30.11
            }
        }
        return $output;
    }

    function render_action_fields_headers(){
        $output = "";
        foreach($this->action_fields as $a=>$action){
            $output.= "<td>";
            if(isset($action['column_name']))
                $output.= $action['column_name'];
            else if(isset($action['display_name']))
                $output.= $action['display_name']; else $output.= $a;
            switch($action['type']){
                case "link":
                    break;
                case "checkbox":
                default:
                    if($action['check_all'] != false)
                        $output.= "<input type='checkbox' name='taf_all_{$a}' id='taf_all_{$a}' value='1' onclick='taf_all('{$a}');' />";
                    break;
            }
            $output.= "</td>";
        }
        if($this->editable !== false)
            $output.= "<td>Edit</td>";
        if($this->deleteable !== false)
            $output.= "<td>Delete</td>";
        return $output;
    }

    function render_action_fields_list(&$row){
        $output="";
        foreach($this->action_fields as $a=>$action){
            $output.= "<td>";
            switch($action['type']){
                case "function":
                    $output.= "<a href='".$this->create_url("action",$row[$this->key],$a,array("ra"=>$this->action))."'>{$action['display_name']}</a>";
                    break;
                case "link":
                    $output.= "<a href='{$action['url']}{$row[$this->key]}'>{$action['display_name']}</a>";
                    break;
                case "action":
                    $output.= "<a href='".$this->create_url($action['action'],$row[$this->key],$action['action_field'])."'>{$action['display_name']}</a>";
                    break;
                case "checkbox":
                default:
                    $output.= "<input type='checkbox' name='taf_{$a}[{$row[$this->key]}]' class='taf_{$a}' value='1' />";
                    break;
            }
            $output.= "</td>";
        }
        if($this->editable !== false)
            $output.= "<td><a href='".$this->create_url("edit",$row[$this->key])."'><img src='/images/main/edit.png' border='0' /></a></td>";
        if($this->deleteable !== false)
            $output.= "<td><a href='".$this->create_url("delete",$row[$this->key])."'><img src='/images/main/remove.png' border='0' /></a></td>";
        return $output;
    }

    function render_form($return = false){
        if(isset($this->custom_templates[$this->action.'_item'])){
            $template = $this->load_template($this->custom_template_directory.$this->custom_templates[$this->action.'_item']);
            foreach($this->fields as $field){
                switch($this->which_field($field)){
                    case 'edit':
                        $template = str_replace('{'.$field['Field'].'}', $this->edit_field_value($field,$this->list), $template);
                        break;
                    case 'display':
                        $template = str_replace('{'.$field['Field'].'}', $this->print_field_value($field,$this->list), $template);
                        break;
                    case false:
                        $template = str_replace('{'.$field['Field'].'}', '', $template);
                }
            }
            $template = $this->template_item_vars($template,$this->id);
            if($return)
                return $template;
            else
                echo $template;
        } else {

            // show form
            if($this->action == "view")
                ;
            if($this->action == "new")
                $new_action = "insert";
            else
                $new_action = "update";
            if($this->action != "view"){
                if($this->has_upload_field()) $multipart_header = "enctype='multipart/form-data'";
                echo "<form action='".$this->create_url()."' id='form_{$this->table}' method='POST' {$multipart_header}><input type='hidden' name='action' id='action' value='{$new_action}' />";
            }
            // use the ID if we are editing a current one
            if($this->action == "edit")
                echo "<input type='hidden' name='id' value='{$this->id}' />";
            if($this->action == "view" && !$this->read_only){
                if($this->editable !== false)
                    echo "<a href='".$this->create_url('edit',$this->id)."'><img src='/images/main/edit.png' border='0' /></a>";
                if($this->deleteable !== false)
                    echo "<a href='".$this->create_url('delete',$this->id)."'><img src='/images/main/remove.png' border='0' /></a>";
            }
            if($this->styles['form_table'])
                $style = "style='{$this->styles['form_table']}'";
            echo "<table class='table_form_table' {$style}>";
            unset ($style);
            if(is_array($this->field_order)){
                foreach($this->field_order as $f){
                    echo $this->render_form_row($this->fields[$f],$this->list);
                }
            } else {
                foreach($this->fields as $f=>$field){
                    echo $this->render_form_row($this->fields[$f],$this->list);
                }
            }

            $this->render_form_linked_tables();

            if($this->action != "view"){
                echo "<tr><td colspan='2'><input type='submit' name='submit' value='{$this->button_names[$new_action]}'>";
                if($this->cancel)
                    echo "<input type='button' name='cancel' value='{$this->button_names['cancel']}' onclick='document.location=\"".$this->create_url()."\";' />";
                if ($this->refer_return) {
                    echo '<input type="hidden" name="refer_return" value="'.$this->refer_return.'" />';
                }
                if ($new_action == 'update' && $this->enable_serial_update) {
                    echo '<input type="checkbox" name="serialupdate" '.($this->serial_update ? 'checked="checked" ' : '').' /> Edit Next Record';
                }
                echo $this->form_buttons_after;
                echo "</td></tr>";
            }
            echo "</table>";
            if($this->action != "view") echo "</form>";
            if($this->action == "view" && !$this->read_only){
                if($this->editable !== false)
                    echo "<a href='".$this->create_url('edit',$this->id)."'><img src='/images/main/edit.png' border='0' /></a>";
                if($this->deleteable !== false)
                    echo "<a href='".$this->create_url('delete',$this->id)."'><img src='/images/main/remove.png' border='0' /></a>";
            }
        }
    }

    function render_form_row(&$field,$row){
        $output = "";
        if($which_field = $this->which_field($field)){
            if(isset($f['type']))
                $field['Type'] = $this->fields[$field['Field']]['type'];
            // double column width row
            if($field['Type'] == "note"){
                if($field['note'] != "")
                    $output .= "<tr><td colspan='2'><h3>{$field['note']}</h3></td></tr>";
                else
                    $output .= "<tr><td colspan='2'><h3>{$field['display_name']}</h3></td></tr>";
            }elseif($field['width']=="full"){
                $output .= "<tr><td colspan='2'>{$field['display_name']}</td></tr>";
                $output .= "<tr><td colspan='2'>";
                // show the field
                if($which_field == "display")
                    $output .= $this->print_field_value($field,$row);
                elseif($which_field == "edit")
                    $output .= $this->edit_field_value($field,$row);
                if($field['default_reset']){
                    $output .= "<input type='button' value='Reset to Default' onclick='reset_field_value(\"{$field['Field']}\");' />";
                }
                $output .= "</td></tr>";
                // column for title and column for field
            } else {
                $output .= "<tr><td valign='top'>";
                $output .= $field['display_name'];
                $output .= "</td><td valign='top'>";
                // show the field
                if($which_field == "display")
                    $output .= $this->print_field_value($field,$row);
                elseif($which_field == "edit")
                    $output .= $this->edit_field_value($field,$row);
                if($field['default_reset']){
                    $output .= "<input type='button' value='Reset to Default' onclick='reset_field_value(\"{$field['Field']}\");' />";
                }
                $output .= "</td></tr>";
            }
        }
        return $output;
    }

    // THIS IS CALLED TO RENDER LINKED TABLES IN view/edit/new MODE
    // (full form)
    function render_form_linked_tables(){
        foreach($this->links as $link => &$link_settings){
            if(!isset($link_settings['table'])) $link_settings['table'] = $link;
            if ($link_settings['list'] === 'each') {
                // is this needed in form view?
                // LOAD THE LIST
                /*
                                $this->load_all_active_list($link_settings);
                                $link_settings['row_count'] = count($link_settings['active_list']);


                                // Set the character to join the URL parameters to the edit_link
                                $joinchar = (strpos($link_settings['edit_link'], $joinchar) !== false) ? ":" : '?';

                                if($link_settings['display_name'])
                                    echo "<tr {$style}><td>{$link_settings['display_name']}".($link_settings['edit_link'] ? " <a href='{$link_settings['edit_link']}{$joinchar}action=new&backlinkname={$this->key}&backlinkvalue={$this->id}'><img src='/images/main/new.png' border='0' /></a>" : '').($link_settings['edit_js'] ? " <a href='' onclick='{$link_settings['edit_js']}.newLink({$this->id})'>New</a>" : '')."</td></tr>"; // TODO changed from below: $row[$this->key] to $this->id
                                for($i = 0; $i < count($link_settings['active_list']); $i++){
                                    echo "<tr id='link_{$link}_{$link_settings['active_list'][$i][$link_settings['key']]}' {$style}>";
                                    foreach($link_settings['active_list'][$i] as $v){
                                        echo "<td>{$v}</td>";
                                    }
                                    if($link_settings['edit_link'] != ''){
                                        echo "<td><a href='{$link_settings['edit_link']}{$joinchar}action=edit&id={$link_settings['active_list'][$i][$link_settings['key']]}'>Edit</a> <a href='{$link_settings['edit_link']}{$joinchar}action=delete&id={$link_settings['active_list'][$i][$link_settings['key']]}'><img src='/images/main/remove.png' border='0' /></a></td>";
                                    }
                                    if($link_settings['edit_js'] != ''){
                                        echo "<td><a href='' onclick='{$link_settings['edit_js']}.editLink({$link_settings['active_list'][$i][$link_settings['key']]})'><img src='/images/main/edit.png' border='0' /></a> <a href='' onclick='{$link_settings['edit_js']}.deleteLink({$link_settings['active_list'][$i][$link_settings['key']]})'><img src='/images/main/remove.png' border='0' /></a></td>";
                                    }
                                    echo "</tr>";
                                }
                */
                /* 	END TODO: This section should mirror the similar section from the LIST MODE below */
            } else {
                // DISPLAY NAME ON THE LEFT
                if(isset($link_settings['display_name']))
                    echo "<tr><td>{$link_settings['display_name']}</td><td>";
                else
                    echo "<tr><td>{$link}</td><td>";

                // LOAD THE LINKED ROWS
                $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->key;
                $local_id = ($this->table) ? $this->list[$local_key] : $this->id;

                if($local_id > 0 && !isset($link_settings['active_list'])){
                    $this->load_all_active_list($link_settings,$local_id );
                }

                $link_settings['row_count'] = count($link_settings['active_list']);

                // IN EDIT/NEW MODE, SHOW A FULL FORM
                if ($this->action == "edit" || $this->action == "new"){
                    // IN EDIT MODE WITH THE full_form OPTION, SHOW THE FORM WITH ADD/REMOVE LINKS
                    if($link_settings['full_form'] === true){
                        // editable forms (1 to many)
                        echo $this->render_full_linked_table_editable($link_settings);
                    } else {
                        // drop down menu (many to many)
                        echo $this->render_linked_table_editable($link_settings);
                    }
                }

                // FULL FORM MODE INDICATES THAT THE LINKED TABLE IS A SUB TABLE OF THE MAIN TABLE - A 1(table) TO MANY (subtable) RELATIONSHIP
                // for view mode, if "display" is set, use the "display" template
                else if($this->action == "view" && is_array($link_settings['active_list'])){
                    if(isset($link_settings['display'])){
                        // IN VIEW MODE WITH THE full_form OPTION, JUST SHOW ALL THE DATA
                        // loop for each entry
                        foreach($link_settings['active_list'] as $l){
                            // loop for each field
                            $display = $link_settings['display'];
                            foreach($l as $f=>$v){
                                if(isset($link_settings['fields'][$f])){
                                    if($link_settings['fields'][$f]['Field'] == "") $link_settings['fields'][$f]['Field'] = $f;
                                    $display = str_replace('{'.$f.'}', $this->print_field_value($link_settings['fields'][$f],$l), $display);
                                }
                            }
                            echo $display;
                            echo $link_settings['seperator'];
                            // insert break here?
                        }
                        // THIS IS A MANY TO MANY RELATIONSHIP
                        // otherwise just list out all the fields
                    } else if ($link_settings['full_form'] === true) {
                        // full form view
                        foreach($link_settings['active_list'] as $l){
                            echo "<div class='subtable'><table>";
                            // SHOW FORM FIELDS
                            foreach($link_settings['fields'] as $f=>&$s){
                                $s['Field'] = $f;
                                $s['form_field'] = "st_{$link}_{$f}_{$l[$link_settings['key']]}";
                                if($this->which_field($s) == "display"){
                                    echo "<tr><td>{$s['display_name']}</td><td>";
                                    echo $this->print_field_value($s,$l);
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
    function render_full_linked_table_editable(&$link_settings){
        $output = "<input type='hidden' name='delete_subtable_{$link_settings['table']}' id='delete_subtable_{$link_settings['table']}' />";
        $output .= "<input type='hidden' name='new_subtable_{$link_settings['table']}' id='new_subtable_{$link_settings['table']}' />";
        if(count($link_settings['active_list']) > 0)
            foreach($link_settings['active_list'] as $l){
                $output .= "<div class='subtable' id='subtable_{$link_settings['table']}_{$l[$link_settings['key']]}'><table>";
                // SHOW FORM FIELDS
                foreach($link_settings['fields'] as $f=>&$s){
                    $link_settings['fields'][$f]['Field'] = $f;
                    $link_settings['fields'][$f]['form_field'] = "st_{$link_settings['table']}_{$f}_{$l[$link_settings['key']]}";
                    $output .= $this->render_form_row($s,$l);
                }
                // ADD REMOVE LINKS
                $output .= "</table>";
                $output .= "<span class='link' onclick='delete_subtable(this)'>{$link_settings['delete_name']}</span>";
                $output .= "</div>";
            }

        // ADD BLANK FORM FOR ADDING NEW LINK
        $output .= "<div class='subtable' id='subtable_{$link_settings['table']}__N_' style='display:none;'><table>";

        // SHOW FORM FIELDS
        foreach($link_settings['fields'] as $f=>&$s){
            $link_settings['fields'][$f]['Field'] = $f;
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

    // returns drop down menu
    // this renders a linked table showing a list of all available options, and a list of
    // all items that are already added to this table item
    // this is a many to many - where you can add any of the options from load_all_complete_list()
    // but you can't edit the actual content unless you go to the table page for that table
    function render_linked_table_editable(&$link_settings){
        // show list of options to ad
        // IN REGULAR MODE IF edit_js? IS TURNED ON
        $output = "";
        if ($link_settings['edit_js'] != '') {
            $output .= "<select name='{$link_settings['table']}_list' id='{$link_settings['table']}_list' ></select>";
            $output .= "<input type='button' name='add_{$link_settings['table']}_button' value='Add {$link_settings['table']}' id='add_{$link_settings['table']}_button' onclick='{$link_settings['edit_js']}.newLink(\"{$this->id}\")' />";

            //DEFAULT VIEW MODE
        } else {
            $this->load_all_complete_list($link_settings);
            $output .= "<select name='{$link_settings['table']}_list' id='{$link_settings['table']}_list' >";
            foreach($link_settings['complete_list'] as $l)
                $output .= "<option value='{$l[$link_settings['key']]}'>{$l[$link_settings['display_column']]}</option>";
            $output .= "</select>";
            $output .= "<input type='button' name='add_{$link_settings['table']}_button' value='Add {$link_settings['table']}' id='add_{$link_settings['table']}_button' onclick='add_link(\"{$link_settings['table']}\")' />";
        }

        //set up initial list - these are already added
        $this->load_all_active_list($link_settings, $this->id);

        // create the hidden array field
        $output .= "<input type='hidden' name='{$link_settings['table']}_input_array' id='{$link_settings['table']}_input_array' value='";
        foreach($link_settings['active_list'] as $init)
            $output .= $init[$link_settings['key']].",";
        $output .= "' /><br /><div id='{$link_settings['table']}_list_container'>";
        // create each item as a viewable deleteable box
        foreach($link_settings['active_list'] as $init)
            $output .= "<div class='{$link_settings['table']}_box' id='{$link_settings['table']}_box_{$init[$link_settings['key']]}'>{$init[$link_settings['display_column']]}
						<a href='#' onclick='javascript:".($link_settings['edit_js'] != '' ? $link_settings['edit_js'].'.deleteLink('.
                    $init[$link_settings['key']].')' : "remove_link(\"{$link_settings['table']}\",{$init[$link_settings['key']]})").";return false;'>X</a></div>";
        $output .= "</div></td></tr>";
        return $output;
    }

    // this loads all links that are actively joined by a foreign key on the remote table
    // or by a link table in between. this is used for a one to many relationship, (1 table row to many links)
    function load_all_active_list(&$link_settings, $row_id){
        $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->key;
        if($link_settings['index']!="")
            // many to many - there will be an index table linking the two tables together
            $link_settings['active_list'] = Database::getInstance()->assoc("SELECT * FROM `{$link_settings['index']}` JOIN `{$link_settings['table']}` USING (`$link_settings[key]`) WHERE `{$local_key}` = '{$row_id}'");
        else
            // 1 to many - each remote table will have a column linking it back to this table
            $link_settings['active_list'] = Database::getInstance()->assoc("SELECT * FROM `{$link_settings['table']}` WHERE `{$local_key}` = '{$row_id}'");
    }

    // this loads all possible optiosn for a link to be joined
    // used in a many to many
    function load_all_complete_list(&$link_settings){
        if($link_settings['access_control'])
            $where = " WHERE {$link_settings['access_control']}";
        $link_settings['complete_list'] = Database::getInstance()->assoc("SELECT * FROM `{$link_settings['table']}` {$where}");
    }

    function pagination(){
        $output = "";
        $pages = ceil($this->list_count / $this->max_per_page);
        if($pages > 1){
            $output.= "Page: ";
            for($i = 1; $i <= $pages; $i++){
                if($this->page == $i)
                    $output.= $i;
                else
                    $output.= " <a href='".$this->create_url("page",$i)."'>{$i}</a> ";
            }
        }
        return $output;
    }

    function set_preset($new_preset){
        $this->preset = $new_preset;
    }

    function create_url($action="",$id=0,$field="",$other=array()){
        $vars = array();
        if($action == "page") $vars['p'] = $id;
        if($action!="") $vars['action'] = $action;
        if($id > 0) $vars['id'] = $id;
        if($this->table_url) $vars['table'] = $this->table;
        if(isset($this->parent_link)) $vars[$this->parent_link] = $this->parent_id;
        if($field != "") $vars['f'] = $field;
        if($this->cur_subset && $this->cur_subset != $this->subset_default) $vars['ss'] = $this->cur_subset;
        if(count($this->additional_action_vars) > 0)
            $vars = array_merge($this->additional_action_vars,$vars);
        if(count($other) > 0)
            $vars = array_merge($vars,$other);

        // serach
        $sort = array();
        if(is_array($this->sort_fields) && count($this->sort_fields) > 0){
            $sort_fields = $this->search_fields;
            if($other['sort']){
                foreach($other['sort'] as $f=>$d){
                    switch($d){
                        case "A": $sort_fields[$f] = "A"; break;
                        case "D": $sort_fields[$f] = "D"; break;
                        case "X":
                            if($this->sort_fields[$f] == "A")
                                $sort_fields[$f] = "D";
                            else
                                $sort_fields[$f] = "A";
                            break;
                    }
                }
            }
            foreach($sort_fields as $f=>$d){
                if($d == "D")
                    $sort[] = "{$f}:D";
                else
                    $sort[] = "{$f}";
            }
            $vars['sort']=implode(";",$sort);
        } else if($other['sort']){
            $sort = array();
            foreach($other['sort'] as $f=>$d){
                switch($d){
                    case "D": $sort[] = "{$f}:D"; break;

                    case "A":
                    default:	 $sort[] = $f; break;
                }
            }
            $vars['sort']=implode(";",$sort);
        }
        // put it all together
        $vars = http_build_query($vars);
        return "{$this->action_file}".($vars!=""?"?".$vars:"");
    }

    function load_template($file){
        if($file == "") return;
        $template = file_get_contents($file);
        $template = str_replace('{table_link_new}',$this->create_url("new"),$template);
        $template = str_replace('{table_header}', $this->header, $template);
        $template = str_replace('{parent_id}', intval($this->parent_id), $template);
        $template = str_replace('{calendar_mode}', $this->calendar_mode, $template);

        // additional action vars
        foreach($this->additional_action_vars as $f=>$v)
            $template = str_replace('{'.$f.'}', $v, $template);

        return $template;
    }

    function template_item_vars($template,$id){
        $template = str_replace('{table_link_edit}',$this->create_url("edit",$id), $template);
        $template = str_replace('{table_link_delete}',$this->create_url("delete",$id), $template);
        $template = str_replace('{table_link_view}',$this->create_url("view",$id), $template);
        $template = str_replace('{key}',$id, $template);

        // look for linked file names
        preg_match_all("/\{table_link_file\.([a-z0-9_]+)\}/i",$template,$matches);
        for($i = 1; $i < count($matches); $i = $i+2){
            $m = $matches[$i][0];
            if($this->fields[$m]['Type'] == 'file'){
                $template = str_replace('{table_link_file.'.$m.'}', $this->create_url("file",$id,$m), $template);
            }
        }

        // if calendar
        if($this->type=='calendar'){
            $prev_year = $next_year = $this->calendar_year;

            $prev_month = $this->calendar_month - 1;
            if($prev_month < 1) { $prev_month += 12; $prev_year--;}

            $next_month = $this->calendar_month + 1;
            if($next_month > 12) { $next_month -= 12; $next_year++;}

            $template = str_replace('{prev_month_link}',$this->create_url('',0,'',array('month'=>$prev_month,'year'=>$prev_year)),$template);
            $template = str_replace('{next_month_link}',$this->create_url('',0,'',array('month'=>$next_month,'year'=>$next_year)),$template);
        }

        return $template;
    }

    /*
    * get_fields() obtains all the column names in $table
    *
    * Uses the "SHOW COLUMNS FROM $this->table" command to list out the columns and obtain their names.
    * It stores results in an associative array $this->fields
    *
    *
    */
    function get_fields(){
        if($this->local_field_list) return;
        // set default fields
        if($this->table)
            $fields = Database::getInstance()->assoc("SHOW COLUMNS FROM `{$this->table}`",'Field');
        else
            $fields = array();
        $this->fields = $this->merge_array_r($this->preset,$fields);
        //make sure there is a 'Field' element and 'display_name' for each $field
        foreach($this->fields as $f=>&$field){
            if($field['display_name'] == ""){
                $field['display_name'] = ucwords(str_replace("_"," ",$f));
            }
            if(!isset($field['Field']))
                $field['Field'] = $f;
            if($field['Type'] == "file"){
                if(isset($field['extension']))
                    $this->fields[$field['extension']]['Type'] = "hidden";
            }
        }
        if(is_array($this->links))
            foreach($this->links as $l=>&$s){
                if($s['display_name'] == ""){
                    $s['display_name'] = ucwords(str_replace("_"," ",$l));
                }
                if($s['add_name'] == "")
                    $s['add_name'] = "Add Item";
                if($s['delete_name'] == "")
                    $s['delete_name'] = "Delete Item";
            }
    }

    function which_field(&$field){
        switch($this->action){
            case "new":
                if($this->user_input_new($field))
                    return "edit";
                elseif($this->user_display_new($field))
                    return "display";
                else
                    return false;
                break;
            case "edit":
                if($this->user_input_edit($field))
                    return "edit";
                elseif($this->user_display_edit($field))
                    return "display";
                else
                    return false;
                break;
            case "view":
                if($this->display_view($field))
                    return "display";
                else
                    return false;
                break;
            case "list":
            default:
                if($this->display_list($field))
                    return "display";
                else
                    return false;
                break;
        }
    }

    // is the field editable in these forms
    function user_input_new(&$field){
        if(isset($field['render_'.$this->action.'_field']))
            return true;
        if($field['Type'] == "note")
            return true;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        if($field['Field'] == $this->key)
            return false;
        if($field['Field'] == $this->parent_link)
            return false;
        if($field['editable'] === false)
            return false;
        if($field['list_only'])
            return false;
        return true;
    }

    function user_input_edit(&$field){
        if(isset($field['render_'.$this->action.'_field']))
            return true;
        if($field['Type'] == "note")
            return true;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        if($field['Field'] == $this->key)
            return false;
        if($field['Field'] == $this->parent_link)
            return false;
        if($field['editable'] === false)
            return false;
        if($field['list_only'])
            return false;
        if($field['set_on_new'])
            return false;
        return true;
    }

    function user_display_new(&$field){
        if($field['list_only'])
            return false;
        if(is_callable($field['display_function']) || is_callable($field['display_new_function']))
            return true;
        if($field['Field'] == $this->parent_link)
            return false;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        return true;
    }

    function user_display_edit(&$field){
        if($field['list_only'])
            return false;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        if(is_callable($field['display_function']) || is_callable($field['display_edit_function']))
            return true;
        if($field['Field'] == $this->parent_link)
            return false;
        return true;
    }

    function display_list(&$field){
        if(is_callable($field['display_function']) || is_callable($field['display_list_function']))
            return true;
        if($field['Field'] == $this->parent_link)
            return false;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        if($field['unlisted'])
            return false;
        return true;
    }

    function display_view(&$field){
        if(is_callable($field['display_function']) || is_callable($field['display_view_function']))
            return true;
        if($field['Type'] == "note" && $field['view'])
            return true;
        if($field['Field'] == $this->parent_link)
            return false;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        if($field['list_only'])
            return false;
        return true;
    }

    // sould we even consider the posted/function value on insert? -- implemented
    function get_value_on_new(&$field){
        if(isset($field['insert_function']))
            return true;
        if(isset($field['submit_function']))
            return true;
        if($field['force_default_new'] || $field['Default'])
            return true;
        if($field['set_on_new'])
            return true;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        if($field['editable'] === false)
            return false;
        if($field['list_only'])
            return false;
        return true;
    }

    // should we even consider the posted/function value on update? -- implemented
    function get_value_on_update(&$field){
        if(isset($field['modified_function']))
            return true;
        if(isset($field['submit_function']))
            return true;
        if($field['Type'] == 'hidden' || $field['hidden'] == 'true')
            return false;
        if($field['Field'] == $this->parent_link)
            return false;
        if($field['editable'] === false)
            return false;
        if($field['list_only'])
            return false;
        if($field['Field'] == $this->key)
            return false;
        if($field['Field'] == $this->key)
            return false;
        return true;
    }

    // old functions here

    /*
        function is_editable(&$field){
            if($field['Field'] == $this->key)
                return false;
            if($field['Field'] == $this->parent_link)
                return false;
            if($field['Type']=='hidden')
                return false;
            if($field['list_only'])
                return false;
            if($field['editable']===false)
                return false;
            return true;
        }
    */

    // should this field be selectable
    /*
        function is_editable_on_new(&$field){
            if($field['set_on_new'])
                return true;
            else return $this->is_editable($field);
        }
    */

    // under these conditions it should appear in list mode
    /*
        function is_listable(&$field){
            if($field['Type']=='hidden')
                return false;
            elseif($field['unlisted'])
                return false;
            elseif($field['Field'] == $this->parent_link)
                return false;
            return true;
        }
    */

    function update_access_table(){
        if(isset($this->access_table)){
            $access_table_values = $this->get_field_values($this->fields, true);
            if(!empty($access_table_values)){
                Database::getInstance()->update($this->access_table, $access_table_values, array_merge($this->access_table_condition, array($this->key => $this->id)));
            }
        }
    }

    function get_field_values(&$field_list, $access_table=false){
        $string = '';

        foreach($field_list as $f=>$field) {
            // check for settings that override user input
            if($this->action == "insert"){
                if(!$this->get_value_on_new($field))
                    continue;
            } else if ($this->action == "update"){
                if(!$this->get_value_on_update($field))
                    continue;
            }
            if($field['Type'] == 'note'){
                continue;
            } if($field['nocolumn']) {
                continue;
            }

            if ($field['table'] == "access" && !$access_table){
                continue;
            } else if (!isset($field['table']) && $access_table){
                continue;
            }

            unset($val);
            $sanitize=false;
            $html = false;
            $ignore = false;

            if(!isset($field['form_field'])) $field['form_field'] = $field['Field'];
            // GET THE FIELD VALUE

            // OVERRIDES

            if($field['force_default_new'] && $this->action == "insert"){
                $val = $field['Default'];
                // developer enterd, could need sanitization
                $sanitize = true;
            } elseif($this->parent_link == $field['Field']){
                // parent link
                $val = $this->parent_id;
                // already sanitized, not needed
                // FUNCTIONS
            } elseif(isset($field['insert_function']) && $this->action == "insert"){
                // function when modified
                $val = $this->preset[$field['Field']]['insert_function']();
                $sanitize = true;
            } elseif(isset($field['modified_function']) && $this->action == "update"){
                $val = $this->preset[$field['Field']]['modified_function']();
                $sanitize = true;
            } elseif(isset($field['submit_function'])){ // covers both unsert_function and modified_function
                $val = $this->preset[$field['Field']]['submit_function']();
                $sanitize = true;

                // DEPRECATED - REPLACE WITH SUBMIT_FUNCTION
                /*
                            } else if(isset($this->fields[$field['Field']]['function'])){
                                // FUNCTION
                                $val = $this->fields[$field['Field']]['function']();
                                if($field['sanitize'] !== false)
                                    $val = $this->input_sanitize($val, true);
                */

                // STANDARD INPUT VALUES

            } else if($field['Type'] == "file"){
                if($_FILES[$field['Field']]['size'] > 0
                    && $_FILES[$field['Field']]['error'] == UPLOAD_ERR_OK
                    && (($field['replaceable'] && $this->action == "update") || $this->action == "insert")){
                    // delete previous file
                    $this->get_row();
                    if($this->list[$f] != "")
                        if(file_exists($this->get_full_file_location($field['directory'],$this->list[$f])))
                            unlink($this->get_full_file_location($field['directory'],$this->list[$f]));
                    // copy the uploaded file to the right directory
                    // needs some security checks -- IMPLEMENT FEATURE - what kind? make sure its not executable?
                    $val = $this->get_new_file_loc($field['directory']);
                    move_uploaded_file($_FILES[$field['Field']]['tmp_name'],$field['directory'].$val);

                    if(isset($field['extension'])){
                        $extention = preg_match("/\.[a-z1-3]+$/",$_FILES[$field['Field']]['name'],$r);
                        $string .= ", `{$field['extension']}` = '".strtolower($r[0])."'";
                    }
                } else {
                    $ignore = true;
                }
                // internally generated value no sanitization needed
            } else if($field['Type'] == "date"){
                // DATE
                $m = intval($_POST[$field['form_field']."_m"]);
                $d = intval($_POST[$field['form_field']."_d"]);
                $y = intval($_POST[$field['form_field']."_y"]);
                if($m > 0 && $d > 0){
                    if($y == 0) $y = date("Y");
                    $val = gregoriantojd($m,$d,$y);
                } else $val = 0;
                if($val == 0 && !$field['allow_blank'])
                    $val = gregoriantojd(date("m"),date("d"),date("Y"));
                // int - no sanitize needed
            } else if($field['Type'] == "time"){
                // TIME
                if($_POST[$field['form_field'].'_h'] == '')
                    $val = 0;
                else {
                    $h = intval($_POST[$field['form_field'].'_h']);
                    $i = intval($_POST[$field['form_field'].'_i']);
                    $a = scrub::text($_POST[$field['form_field'].'_a']);
                    if($a == "PM") $h += 12;
                    $val = ($h*60)+$i;
                }
                // int - no sanitize needed
            } else if($field['Type'] == "datetime"){
                // DATE TIME
                $m = intval($_POST[$field['form_field'].'_m']);
                $d = intval($_POST[$field['form_field'].'_d']);
                $y = intval($_POST[$field['form_field'].'_y']);
                $h = intval($_POST[$field['form_field'].'_h']);
                $i = str_pad(intval($_POST[$field['form_field'].'_i']),2,0,STR_PAD_LEFT);
                $a = scrub::text($_POST[$field['form_field'].'_a']);
                $val = strtotime("{$m}/{$d}/{$y} {$h}:{$i} {$a}");
                // int - no sanitize needed
            } else if ($field['Type'] == "checklist" || $field['Type'] == "poplist"){
                $vals = "";
                $maxi = 0;
                foreach($field['options'] as $i => $opt){
                    if(is_array($opt)){
                        $maxi = max($maxi,$opt[0]);
                    } else {
                        $maxi = max($maxi,$i);
                    }
                }
                for($i = 0; $i <= $maxi; $i++){
                    $vals .= ($_POST[$field['form_field'].'_'.$i] == 1 || $_POST[$field['form_field'].'_'.$i] == "on") ? 1 : 0;
                }
                $val = bindec(strrev($vals));
            } else if(isset($_POST[$field['form_field']])){
                // generic text, must be sanitizeed
                $val = $_POST[$field['form_field']];
                $sanitize = true;
                $html = true;
            }

            // if there is an alternate default value
            if (!isset($val) && $this->action == "insert" && isset($field['Default'])){
                $val = $field['Default'];
                // developer input - could require sanitization
                $sanitize = true;
            }

            // sanitize
            if($sanitize &&
                !($this->action == "insert" && ($field['insert_sanitize'] === false || $field['submit_sanitize'] === false)) &&
                !($this->action == "update" && ($field['modify_sanitize'] === false || $field['submit_sanitize'] === false))
            ){
                $val = $this->input_sanitize($val,$html);
                /*
                                if(!isset($field['encrypted']) || $field['encrypted']==false) // don't add the qutoes if it's getting encrypted first
                                    $val = $this->add_db_quotes($val);
                */
            }

            // if the value needs to be encrypted
            if($field['encrypted']){
                $val = $this->encrypt($this->table,$field['Field'],$val);
            }

            if(!$ignore) {
                $output[$field['Field']] = $val;
            }
        }
        return $output;
    }

    function decode_bool_group($int){
        return str_split(strrev(decbin($int)));
    }

    // get the int val of a specific bit - ie convert 1 (2nd col form right or 10) to 2
    // this way you can search for the 2nd bit column in a checlist with: "... AND col&".table::get_bit_int(2)." > 0"
    public static function get_bit_int($bit){
        bindec("1".str_repeat("0", $bit));
    }

    function init_sanitizer(){
        global $home_path;

        if(!$this->sanitizer_inited){
            require_once $home_path.'/include/htmlpurifier/HTMLPurifier.auto.php';
//			require_once $home_path.'/include/htmlpurifier/HTMLPurifier.includes.php';

            /* 			$config = HTMLPurifier_Config::createDefault(); */
            /*
                        $config->set('Core.DefinitionCache', null);
                        $config('Cache.SerializerPath', '/home/user/absolute/path');
            */
            $this->sanitizer = new HTMLPurifier();//$config


            $this->sanitizer_inited = true;
        }

    }

    function input_sanitize($val, $allow_html = false){

        $val = stripslashes($val);

        if($allow_html === true && $this->trusted){
            $clean_html = scrub::html($val, '', '', TRUE);
        }
        elseif($allow_html === true){
            $clean_html = scrub::html($val);
        }
        elseif($allow_html)
            $clean_html = scrub::html($val,$allow_html);
        else
            $clean_html = scrub::text($val);
        return $clean_html;
    }

    function encrypt($table,$column,$value){
        if($value == "") return "";

        global $encryption_engine_url;
        $fields = "c=e&t={$table}&f={$column}&d=".urlencode($value);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $encryption_engine_url);
        curl_setopt($ch,CURLOPT_POST,4);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public static function decrypt($data){
        if($data=="") return "";

        global $encryption_engine_url;
        $fields = "c=d&d=".$data;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $encryption_engine_url);
        curl_setopt($ch,CURLOPT_POST,2);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    function get_key(){
        if($this->key == "" && $this->table != "")
            $this->key = Database::getInstance()->field('Column_name',"SHOW KEYS FROM `{$this->table}` WHERE Key_name = 'PRIMARY'");
    }


    /*
    * get_row() gets a single entry from the table based on $this->id

    * Constructs a database query based on the following class variables:
    * @param string $table->table			the table to query
    * @param string $table->key		the table name of the parent link (foreign key table)
    * @param string $table->id		the id (foreign key) of the parent_link with which to link

    * @return stores result in $list class variable (no actual return result from the method)

    */
    function get_row($force=true){
        if($force == false && count($this->list) == 0) return false;

        $where = "";
        $this->get_key();

        if($this->parent_link && $this->parent_id){
            $where .= " AND {$this->parent_link}={$this->parent_id}";
        }
        if($this->list_where != "")
            $where .= " AND ({$this->list_where})";
        if($this->access_control != "")
            $where .= " AND ({$this->access_control})";
        if($this->singularity)
            $where .= " AND `{$this->singularity}`='{$this->singularity_id}'";
        if($this->access_table){
            $join = "LEFT JOIN {$this->access_table} ";
            if($this->access_table_join_on)
                $join .= "ON ".$this->access_table_join_on;
            else $join .= "ON ({$this->access_table}.{$this->key}={$this->table}.{$this->key})";
            $where .= " AND ".$this->access_table_condition;
        }
        if($this->table) {
            $this->list = Database::getInstance()->assoc1("SELECT * FROM `{$this->table}` {$join} WHERE `{$this->table}`.`{$this->key}` = '{$this->id}' {$where}");
        }
    }


    /*
    * get_list() obtains all the rows from the table

    * Constructs a database query based on the following class variables:
    * @param string $table->table			the table to query
    * @param string $table->parent_link		the table name of the parent link (foreign key table)
    * @param string $table->parent_id		the id (foreign key) of the parent_link with which to link
    * @param string $table->list_where		?
    * @param string $table->access_control	?
    * @param string $table->sort			names of columns, separated by commas to sort by.

    * @return stores result in $list class variable (no actual return result from the method)

    */
    function get_list(){

        // check for required variables
        if($this->table == "")
            return;
        /* 			die("TABLE NOT SET"); */

        // build WHERE qualification
        $where = "";
        if($this->parent_link && $this->parent_id){
            $where .= " AND {$this->parent_link}={$this->parent_id}";
        }
        if($this->list_where != "")
            $where .= " AND ({$this->list_where})";
        if($this->access_control != "")
            $where .= " AND ({$this->access_control})";
        if($this->action == "autocomplete"){
            $where .= " AND `{$_POST['field']}` LIKE '{$_POST['st']}%'";
        }
        if($this->access_table){
            $join = "LEFT JOIN {$this->access_table} ";
            if($this->access_table_join_on)
                $join .= "ON ".$this->access_table_join_on;
            else $join .= "ON ({$this->access_table}.{$this->key}={$this->table}.{$this->key})";
            $where .= " AND ".$this->access_table_condition;
        }
        if($this->cur_subset)
            if($this->subset[$this->cur_subset])
                $where .= " AND {$this->subset[$this->cur_subset]}";
        if($this->action == "search"){
            $this->additional_action_vars['ste'] = urlencode(scrub::text($_POST['ste']));
            $search_terms = explode(" ",scrub::text($_POST['ste']));
            $search = array();
            foreach($search_terms as $t)
                foreach($this->search_fields as $f)
                    $search[] = "`{$f}` LIKE '%{$t}%'";
            $where .= " AND (".implode(" OR ", $search).") ";
        }
        /* 		echo $where; */

        $where = preg_replace("/^ AND/", "WHERE", $where);

        // get the page count
        $count = Database::getInstance()->assoc1("SELECT COUNT(*) as count FROM `{$this->table}` {$join} {$where}");
        $this->list_count = $count['count'];
        $start = (max(1,$this->page) - 1)*$this->max_per_page;

        // validate the sort order
        if ($this->sort != "") {
            $sort = " ORDER BY " . $this->sort;
        }

        if($this->join_where){
            $join = "LEFT JOIN {$this->join_where['table']} ";
        }

        if($this->action == "autocomplete"){
            $field_list = "`{$_POST['field']}`,`{$this->key}`";
            $sort = "ORDER BY `{$_POST['field']}` ASC";
        } else {
            $field_list = "*";
        }

        // get the list
        /*  		echo "SELECT {$field_list} FROM `{$this->table}` {$join} {$where} {$sort} LIMIT {$start},{$this->max_per_page}",$this->key;  */
        $this->list = Database::getInstance()->assoc("SELECT {$field_list} FROM `{$this->table}` {$join} {$where} {$sort} LIMIT {$start},{$this->max_per_page}",$this->key);
    }

    function execute_task(){
        /* 		xdebug_print_function_stack(); */
        // do we load a subset or ss vars?
        if(isset($_REQUEST['ss'])) $this->cur_subset = Scrub::variable($_REQUEST['ss']);
        elseif($this->subset_default) $this->cur_subset = $this->subset_default;

        // if the table is not set explicitly, look for one in the url
        if(!isset($this->table)){
            if(isset($_REQUEST['table'])){
                $this->table = scrub::variable($_REQUEST['table']);
                $this->table_url = true;
            }
            else return false;
        }

        // see if we are calling an action from a link
        $action = Request::get('action');
        if($action == "action" && isset($this->action_fields[$_GET['f']])){
            switch($this->action_fields[$_GET['f']]['type']){
                case "function":
                    $this->id = intval($_GET['id']);
                    $this->get_row();
                    $this->action_fields[$_GET['f']]['function']($this->list);
                    header("Location: ".$this->create_url($_GET['ra'], $row[$this->key]));
                    exit;
                    break;
            }
        }

        // see if we have a default action to use
        if($this->action == ""){
            $this->action = $this->default_action;
            $using_default = true;
        }

        // check for a singularity, only allow edit/update (this means a user only has access to one of these entries, so there is no list view)
        if($this->singularity){
            $row = Database::getInstance()->selectRow($this->table, array($this->singularity => $this->singularity_id));
            if(count($row) > 0) $singularity_exists = true;
            if($singularity_exists) $this->id = $row[$this->key];
            // there can be no "new", "delete", "delconf", "list"
            if($this->action == "new" || $this->action == "edit" || $this->action == "delete" || $this->action == "delconf" || $this->action == "list" || $this->action == ""){
                if($singularity_exists)
                    $this->action = "edit";
                else
                    $this->action = "new";
            }
            // if there is no current entry, an edit becomes an insert
            if($this->action == "update" || $this->action == "insert"){
                if($singularity_exists)
                    $this->action = "update";
                else
                    $this->action = "insert";
            }
        }

        $this->get_key();
        switch($this->action){
            case "search":
                $this->get_fields();
                $output = array();
                $output['d'] = intval($_POST['i']);
                $this->get_list();
                $output['html'] = $this->render_list();
                echo json_encode($output);
                exit;
                break;
            case "pop_return": break;
            case "autocomplete":
                $this->get_list();
                $output = Array("list"=>$this->list,"search"=>$_POST['st']);
                echo json_encode($output);
                exit;
                break;
            case "file":
                $this->get_fields();
                $field = $_GET['f'];
                $this->get_row();
                if($this->fields[$field]['Type'] == 'file' && count($this->list)>0){ //&& isset($this->fields[$field]['extension'])
                    $file = $this->get_full_file_location($this->fields[$field]['directory'],$this->list[$field]);
                    if(!file_exists($file)) die("No File Uploaded");
                    switch($this->list[$this->fields[$field]['extension']]){
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
            case "new":
                if(!$this->addable)  // FAILSAFE
                    break;
                $this->get_fields();
                break;
            case "insert":
                if(!$this->addable)  // FAILSAFE
                    break;
                if($this->table){
                    $this->get_fields();
                    $values = $this->get_field_values($this->fields);
                    $this->id = Database::getInstance()->insert($this->table, $values, $this->update_on_duplicate_key ? $values : false);
                    if($this->post_actions['after_insert']){
                        $this->get_row();
                        $this->post_actions['after_insert']($this->list);
                    }
                    elseif($this->post_actions['after_post']){
                        $this->get_row();
                        $this->post_actions['after_post']($this->list);
                    }
                }
                $this->set_posted_links();
                if(isset($_REQUEST['pf'])){
                    // if we are in a popup, redirect to the popup close script page
                    header("Location: ".$this->create_url('pop_return',$this->id));
                    exit;
                }
                break;
            case "edit":
                if(!$this->editable)  // FAILSAFE
                    break;
            case "view":
                $this->get_fields();
                $this->get_row();
                if($this->action == "edit" && $using_default && count($this->list)==0 && $this->table != ''){
                    $this->action = "new";
                    $this->execute_task();
                }
                break;
            case "update":
                if(!$this->editable)  // FAILSAFE
                    break;
                $this->get_fields();
                $new_values = $this->get_field_values($this->fields);
                if (empty($new_values)) {
                    // no update to make, probably trying to update a row with all disabled fields, or only linked entries?
                } else {
                    /* 					echo "UPDATE `{$this->table}` SET {$string} WHERE `{$this->key}` = '{$this->id}'"; exit; */
                    Database::getInstance()->update($this->table, $new_values, array($this->key => $this->id));
                }
                $this->update_access_table();
                $this->set_posted_links();
                // If serial update is set, set the next action to be an edit of the next higest key,
                // otherwise, go back to the list.
                if($this->post_actions['after_update']){
                    $this->get_row();
                    $this->post_actions['after_update']($this->list);
                } elseif($this->post_actions['after_post']){
                    $this->get_row();
                    $this->post_actions['after_post']($this->list);
                }

                if ($this->enable_serial_update && $this->serial_update) {
                    // Store the next highest key (if existant)
                    $nextkey = NULL;
                    $nextkey = Database::getInstance()->selectField(array('mykey' => ' MIN('.$this->key.')'), $this->table, array($this->key => array(' > ', $this->id)));
                    if ($nextkey) {
                        $this->id = $nextkey;
                        $this->get_row();
                        $this->action = 'edit';
                    } else {
                        // No higher key exists, drop back to the list
                        $this->serial_update = false;
                    }
                }
                break;
            case "delete":
                if(!$this->deleteable) // FAILSAFE
                    break;
                if($this->delconf)
                    break;
                $_POST['delconf'] = "Yes";
            case "delconf":
                if(!$this->deleteable) // FAILSAFE
                    break;
                if($_POST['delconf'] == "Yes"){
                    // loop through and delete any files
                    $this->get_fields();
                    $this->get_row();
                    foreach($this->fields as $f=>$field){
                        if($field['Type'] == "file")
                            if(file_exists($this->get_full_file_location($field['directory'],$this->list[$f])))
                                unlink($this->get_full_file_location($field['directory'],$this->list[$f]));
                    }
                    Database::getInstance()->delete($this->table, array($this->key => $this->id));
                }
            case "list_action":
            case "list":
            case "":
            default:
                $this->action = "list";
                break;
        }
        if(isset($this->function_after[$this->action])){
            $this->function_after[$this->action]();
        }
        if($this->submit_redirect && isset($this->action_after[$this->action])){
            header("Location: ".$this->create_url($this->action_after[$this->action],$this->id));
            exit;
        }
    }

    function check_default_row_click(){
        if(!isset($this->row_click) && $this->editable)
            $this->row_click = Array("type"=>"action","action"=>"edit");
    }

    function js_init_calendar(){
        $jsvars = array('action_file'=>$this->action_file);
        JS::inline('calendar_data=".json_encode($jsvars).";');
    }

    function js_init_data(){
        $table_data = array();
        if($this->row_click){
            $table_data['row_click'] = $this->row_click;
            if(isset($this->table_url))
                $table_data['table'] = $this->table;
            if($this->parent_link)
                $table_data['parent_link'] = $this->parent_link;
            if($this->parent_id)
                $table_data['parent_id'] = $this->parent_id;
            $table_data['action_file'] = $this->action_file;
            if(count($this->additional_action_vars) > 0)
                $table_data['vars'] = $this->additional_action_vars;
        }
        $js_startup = '';
        foreach($this->fields as $f => $field){
            if($field['autocomplete']){
                $js_startup .= '$(".table_autocomplete").keyup(table_autocomplete);';
                $use_autocomplete = true;
            }
            if($field['default_reset']){
                $table_data['defaults'][$f] = $field['Default'];
            }
            if($field['Type'] == "div"){
                $include_ck = true;
                $js_startup .= '$("#'.$f.'_div").attr("contentEditable", "true");
                table_div_editors["'.$f.'"]=CKEDITOR.inline("'.$f.'_div",CKEDITOR.config.toolbar_Full);';
            }
        }
        foreach($this->links as $link=>$link_settings){
            if(($link_settings['include_blank'] == "if_empty" && $link_settings['row_count'] == 0)
                || $link_settings['include_blank'] == "always")
                $js_startup .= 'new_subtable("'.$link.'");';
        }

        if($include_ck){
            JS::add('/ckeditor/ckeditor.js');
            JS::inline("table_toolbar=CKEDITOR.config.toolbar_Full;
            table_div_editors={};
            ".$js_startup."
            $('#form_{$this->table}').submit(function(){ for(i in table_div_editors){ $('#'+i).val(table_div_editors[i].getData());}});");
        }

        if(count($table_data) > 0 || $use_autocomplete || $js_startup){
            if(count($table_data) > 0 || $use_autocomplete)
                JS::inline ("var table_data=".json_encode($table_data).";");
            if($js_startup)
                JS::startup ($js_startup);
        }
    }

    function get_new_file_loc($dir){
        // select random directory
        if(substr($dir,-1) == "/")
            $dir = substr($dir,0,-1);
        do{
            $rand_dir = "/".rand(10,999)."/".rand(10,999)."/";
            if(!file_exists($dir.$rand_dir))
                mkdir($dir.$rand_dir,0755,true);
        } while (count(scandir($dir.$rand_dir))>1000);
        // create random file name
        do{
            $rand_file = sha1(rand(0,92387542938293847));
        }while(file_exists($dir.$rand_dir.$rand_file));

        // return only random dir and file
        return $rand_dir.$rand_file;

    }

    function get_full_file_location($dir,$file){
        $f = $dir."/".$file;
        $f = str_replace("//","/", $f);
        $f = str_replace("//","/", $f);
        return $f;
    }

    function has_upload_field(){
        foreach($this->fields as $f)
            if($f['Type'] == 'file')
                return true;
    }

    function set_posted_links(){
        foreach($this->links as $link => $link_settings){
            // FOR 1 (local) TO MANY (foreign)
            if($link_settings['full_form']){
                if(!isset($this->list))
                    $this->get_row();
                $local_key = isset($link_settings['local_key']) ? $link_settings['local_key'] : $this->key;
                $local_id = isset($this->list[$local_key]) ? $this->list[$local_key] : $this->id;

                if($this->action == "update"){
                    // delete
                    $deleteable = preg_replace("/,$/","",$_POST['delete_subtable_'.$link]);
                    if($deleteable != ""){
                        Database::getInstance()->query("DELETE FROM {$link} WHERE {$link_settings['key']} IN ($deleteable) AND `{$local_key}` = '{$local_id}'");
                        /* 						echo "DELETE FROM {$link} WHERE {$link_settings['key']} IN ($deleteable) AND `{$local_key}` = '{$local_id}'"; */
                    }
                    // update
                    $list = Database::getInstance()->assoc("SELECT * FROM `{$link}` WHERE `{$local_key}` = '{$local_id}' {$sort}");
                    foreach($list as $l){
                        foreach($link_settings['fields'] as $f=>$field){
                            /* 							if(!isset($_POST['st_'.$link.'_'.$f.'_'.$l[$link_settings['key']]])) continue 2; */
                            $link_settings['fields'][$f]['Field'] = $f;
                            $link_settings['fields'][$f]['form_field'] = "st_{$link}_{$f}_{$l[$link_settings['key']]}";
                        }
                        $field_values = $this->get_field_values($link_settings['fields']);
                        Database::getInstance()->update($link, $field_values, array($local_key => $local_id, $link_settings['key'] => $l[$link_settings['key']]));
                    }
                }
                // insert new
                $new_subtables = explode(",", $_POST['new_subtable_'.$link]);
                foreach($new_subtables as $i) if ($i != ""){
                    foreach($link_settings['fields'] as $f=>$field){
                        /* 						if(!isset($_POST["st_{$link}_{$f}_-{$i}"])) continue 2; */
                        $link_settings['fields'][$f]['Field'] = $f;
                        $link_settings['fields'][$f]['form_field'] = "st_{$link}_{$f}_-{$i}";
                    }
                    $field_values = $this->get_field_values($link_settings['fields']);
                    /* 					echo "INSERT INTO {$link} SET {$field_string}, `{$local_key}` = '{$local_id}'"; */
                    Database::getInstance()->insert($link, $field_values, array($local_key => $local_id));
                }
            }
            else if ($link_settings['index']) {
                // CLEAR OUT OLD SETTINGS
                Database::getInstance()->query("DELETE FROM {$link_settings[index]} WHERE {$this->key} = {$this->id}");
                // GET INPUT ARRAY
                $list = explode(",",$_POST[$link.'_input_array']);
                foreach($list as $l)
                    if($l != ''){
                        Database::getInstance()->query("INSERT INTO {$link_settings[index]} SET {$this->key} = {$this->id}, {$link_settings[key]}=$l");
                    }
            }
        }
    }

    // first layer is merged, second layer is overwritten
    function merge_array_r($start,$new){
        if(is_array($new)){
            // for each field in the mysql array
            foreach($new as $k => $v){
                // if it's set in the preset array, then copy the attributes
                if(isset($start[$k])){
                    foreach($new[$k] as $j=>$w){
                        if(!isset($start[$k][$j]))
                            $start[$k][$j] = $w;
                    }
                } else {
                    $start[$k] = $new[$k];
                }
            }
        }
        return $start;
    }

    // print field or print editable field
    function print_field_value($field,&$row=''){
        if($row == "") $v = $field['Value'];
        else $v = $row[$field['Field']];

        if($field['encrypted']){
            $v = table::decrypt($v);
        }

        // set the default value if new
        if($this->action == "new" && isset($field['Default']))
            $v = $field['Default'];
        /* 			$field['Value'] = $this->action == "new" ? $field['Default'] : $this->list[$field['Field']]; */

        if(is_callable($field['render_'.$this->action.'_field'])){
            return $field['render_'.$this->action.'_field']($row);
        } else if(is_callable($field['display_function'])){
            return $field['display_function']($row);
        } else {
            switch(preg_replace("/\([0-9]+\)/","",$field['Type'])){
                case 'lookup':
                    // a lookup will translate to a value drawn from the lookup table based on the key value
                    if ($field['lookuptable'] && $field['display_column']) {
                        if ($v) {
                            $fk = isset($field['lookupkey']) ? $field['lookupkey'] : $field['Field'];
                            if($field['filter'])
                                $filter = "AND {$field['filter']}";
                            $value = Database::getInstance()->assoc1("SELECT `{$field['display_column']}`, `{$fk}` FROM `{$field['lookuptable']}` WHERE `{$fk}` = '{$v}' {$filter}");
                            return $value[$field['display_column']];
                        }
                    } else {
                        return $v;
                    }
                    break;
                case 'yesno':
                    $field['options'] = Array(1=>'No',2=>'Yes');
                case 'state':
                    if($field['Type'] == "state")
                        $field['options'] = $this->state_options();
                case 'select':
                    if(is_array($field['options'][$v]))
                        return $field['options'][$v]['V'];
                    else
                        return $field['options'][$v];
                    break;
                case 'file':
                    // display thumbmail
                    break;
                case 'text':
                case 'mediumtext':
                case 'longtext':
                case 'div':
                    if($this->action == "list" || $this->action == "search"){//$this->action == "view"
                        $v = strip_tags($v);
                        if(strlen($v) > 64)
                            return substr($v,0,64)."...";
                        else
                            return $v;
                    }
                    else // edit should show full text
                        return $v;
                    break;
                case 'time':
                    if($v == 0) return '';
                    $i = str_pad($v%60,2,0,STR_PAD_LEFT);
                    $h = ($v-$i)/60;
                    if($h > 12){
                        $a = "PM";
                        $h -= 12;
                    } else {
                        $a = "AM";
                    }
                    return "{$h}:{$i} {$a}";
                    break;
                case 'date':
                    if($v == 0) return '';
                    $date = explode('/',jdtogregorian($v));
                    return "{$date[0]}/{$date[1]}/{$date[2]}";
                    break;
                case 'datetime':
                    if($v == 0)
                        return "";
                    else
                        return date('m/d/Y h:ia',$v);
                    break;
                case 'checkbox':
                    return "<input type='checkbox' disabled ".(($v==1)?"checked":"")." />";
                    break;
                case 'checklist':
                    $vals = $this->decode_bool_group($v);
                    $output = "";
                    foreach($field['options'] as $i => $opt){
                        if(is_array($opt)){
                            $id = $opt[0];
                            $name = $opt[1];
                        } else {
                            $id = $i;
                            $name = $opt;
                        }
                        $output .= "<div class='checlist_item'><input type='checkbox' disabled ".(($vals[$id]==1)?"checked":"")." />{$name}</div>";
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

    function convert_quotes($v){
        return str_replace("'", "&apos;", str_replace('"',"&quot;",$v));
    }

    function add_db_quotes($v){
        return str_replace("'", "\\'", str_replace('"', '\\"', $v));
    }

    function edit_field_value($field,&$row=''){
        if($row == "") $v = $field['Value'];
        else $v = $row[$field['Field']];

        if(!isset($field['form_field']))
            $field['form_field'] = $field['Field'];
        if(isset($this->preset[$field['Field']]['render_'.$this->action.'_field'])){
            $this->get_row(false);
            return $this->preset[$field['Field']]['render_'.$this->action.'_field']($this->list);
        }

        // prepare value
        if(!isset($field['Value']) )$field['Value'] = $v;
        if($field['encrypted']){
            $field['Value'] = table::decrypt($field['Value']);
        }

        // set the default value if new
        if($this->action == "new" && isset($field['Default']))
            $field['Value'] = $field['Default'];

        // print form input
        switch(preg_replace("/\([0-9]+\)/","",$field['Type'])){
            case 'text':
            case 'mediumtext':
            case 'longtext':
                init_ckeditor();
                $config = array();
                switch(strtolower($field['editor'])){
                    case 'full':		$config['toolbar']="CKEDITOR.config.toolbar_Full"; break;
                    case 'print':		$config['toolbar']="CKEDITOR.config.toolbar_Print"; break;
                    case 'basic_image':	$config['toolbar']="CKEDITOR.config.toolbar_Basic_Image"; break;
                    case 'basic':
                    default:			$config['toolbar']="CKEDITOR.config.toolbar_Basic"; break;
                }

                if($field['absolute']){
                    /*
                                        $config['filebrowserBrowseUrl'] = "/ckfinder/ckfinder.html";
                                        $config['filebrowserImageBrowseUrl'] = "/ckfinder/ckfinder.html?type=Images";
                                        $config['filebrowserFlashBrowseUrl'] = "/ckfinder/ckfinder.html?type=Flash";
                                        $config['filebrowserUploadUrl'] = "/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files";
                                        $config['filebrowserImageUploadUrl'] = "/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images";
                                        $config['filebrowserFlashUploadUrl'] = "/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash";
                    */
                }

                if($field['height'])
                    $config['height'] = $field['height'];
                $ckeditor->returnOutput = true;
                return $ckeditor->editor($field['form_field'], $field['Value'],$config);
                break;
            case 'div':
                if($field['Value'] == "")
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
            case 'file':
                $return = "";
                if(($field['Value'] != "" && $field['replaceable'] !== false) || $field['Value'] == "")
                    $return .= "<input type='file' name='{$field['form_field']}' id='{$field['form_field']}' />";
                if($field['Value']){
                    // display current thumbnail
                }
                return $return;
                break;
            case 'time':
                return Field::timePop($field['form_field'],$field['Value'],$field['allow_blank']?true:false);
                break;
            case 'date':
                $return = Field::datePop($field['form_field'],$field['Value'],$field['allow_blank']?true:false,$field['start_year']);
                return $return;
                break;
            case 'datetime':
                return Field::dateTimePop($field['form_field'],$field['Value'],$field['allow_blank']?true:false,$field['start_year']);
                break;
            case 'lookup':
            case 'yesno':
            case 'state':
            case 'select':
                if($field['Type'] == "lookup"){
                    if($field['filter'])
                        $filter = "WHERE {$field['filter']}";
                    $options = Database::getInstance()->assoc("SELECT {$field['display_column']} as V, {$field['Field']} FROM {$field['lookuptable']} {$filter}",$field['Field']);
                }
                elseif($field['Type'] == "yesno")
                    $options = Array(1=>'No',2=>'Yes');
                elseif($field['Type'] == "state")
                    $options = $this->state_options();
                else
                    $options = $field['options'];
                if(!is_array($options)) return false;

                $output = "<select name='{$field['form_field']}' id='{$field['form_field']}'>";
                if($field['allow_blank'])
                    $output .= '<option value=""></option>';
                foreach($options as $k=>$v){
                    $output .= "<option value='{$k}'".(($field['Value'] == $k) ? 'selected="selected"' : '').'>'
                        .strip_tags((is_array($v)?$v['V']:$v)).'</option>';
                }
                $output .= '</select>';
                if($field['pop_add']){
                    if($field['table_url']) $location = $field['table_url'];
                    else $location = "table.php?table=".$field['lookuptable'];
                    $output .= "<a onclick='new_pop(\"{$location}\",\"{$field['form_field']}\",\"{$field['display_column']}\")'>Add New Item</a>";
                }
                return $output;
                break;
            case 'range':
                $output = "<select name='{$field['form_field']}' id='{$field['form_field']}'>";
                if($field['allow_blank'])
                    $output .= '<option value="0"></option>';
                if($field['start'] < $field['end']){
                    for($k = $field['start']; $k <= $field['end']; $k++)
                        $output .= "<option value='{$k}'".(($field['Value'] == $k) ? 'selected="selected"' : '').">{$k}</option>";
                }
                $output .= '</select>';
                return $output;
                break;
            case 'checkbox':
                return "<input type='checkbox' name='{$field['form_field']}' id='{$field['form_field']}' value='1' ".(($field['Value']==1)?"checked":"")." />";
                break;
            case 'note':
                return $field['note'];
                break;
            case 'checklist':
                $vals = $this->decode_bool_group($field['Value']);
                $output = "";
                foreach($field['options'] as $i => $opt){
                    if(is_array($opt)){
                        $id = $opt[0];
                        $name = $opt[1];
                    } else {
                        $id = $i;
                        $name = $opt;
                    }
                    $output .= "<div class='checlist_item'><input type='checkbox' name='{$field['form_field']}_{$id}' value='1' ".(($vals[$id]==1)?"checked":"")." />{$name}</div>";
                }
                return $output;
                break;
            case 'varchar':
            case 'char':
                preg_match("/(.+)\(([0-9]+)\)/i",$field['Type'], $array);
                $size = "maxlength='{$array[2]}'";
            default:
                if($field['max_length'])
                    $size = "maxlength='{$field['max_length']}'";
                if($field['autocomplete'])
                    $autocomplete = "class='table_autocomplete' autocomplete='off'";
                return "<input type='text' name='{$field['form_field']}' id='{$field['form_field']}' value='".$this->convert_quotes($field['Value'])."' {$size} {$autocomplete} />";
                break;

        }
    }


    /* basic data type functions */

    function dt_us_state(){
        return array("Type"=>"select","display_name"=>"State","options"=>$this->state_options());
    }

    function state_options(){
        return array("AK"=>"AK" , "AL"=>"AL", "AR"=>"AR", "AZ"=>"AZ", "CA"=>"CA"
        , "CO"=>"CO", "CT"=>"CT", "DC"=>"DC", "DE"=>"DE" , "FL"=>"FL", "GA"=>"GA", "HI"=>"HI", "IA"=>"IA"
        , "ID"=>"ID", "IL"=>"IL", "IN"=>"IN", "KS"=>"KS" , "KY"=>"KY", "LA"=>"LA", "MA"=>"MA", "MD"=>"MD"
        , "ME"=>"ME", "MI"=>"MI", "MN"=>"MN", "MO"=>"MO" , "MS"=>"MS", "MT"=>"MT", "NC"=>"NC", "ND"=>"ND"
        , "NE"=>"NE", "NH"=>"NH", "NJ"=>"NJ", "NM"=>"NM" , "NV"=>"NV", "NY"=>"NY", "OH"=>"OH", "OK"=>"OK"
        , "OR"=>"OR", "PA"=>"PA", "PR"=>"PR", "RI"=>"RI" , "SC"=>"SC", "SD"=>"SD", "TN"=>"TN", "TX"=>"TX"
        , "UT"=>"UT", "VA"=>"VA", "VI"=>"VI", "VT"=>"VT" , "WA"=>"WA", "WI"=>"WI", "WV"=>"WV", "WY"=>"WY");
    }
}
