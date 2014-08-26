<?php

namespace Lightning\Tools;

use Exception;
use PDO;
use PDOException;
use PDOStatement;

class Database extends Singleton {
    /**
     * The mysql connection.
     *
     * @var PDO
     */
    var $connection;

    /**
     * Determines if queries and errors should be collected and output.
     *
     * @var boolean
     */
    private $verbose = false;

    /**
     * An array of all queries called in this page request.
     *
     * @var array
     */
    private $history = array();

    /**
     * The result of the last query.
     *
     * @var PDOStatement
     */
    private $result;

    /**
     * The timer start time.
     *
     * @var float
     */
    private $start;

    /**
     * The mysql execution end time.
     *
     * @var float
     */
    private $end1;

    /**
     * The php execution end time.
     *
     * @var float
     */
    private $end2;

    /**
     * The total number of queries executed.
     *
     * @var integer
     */
    private $query_count = 0;

    /**
     * The total time to execute mysql queries.
     *
     * @var integer
     */
    private $mysql_time = 0;

    /**
     * The total time to execute the php post processing of mysql queries data.
     *
     * @var integer
     */
    private $php_time = 0;

    /**
     * Whether this is in read only mode.
     *
     * @var boolean
     */
    private $read_only = FALSE;

    /**
     * Whether the current connection is in the transaction state.
     *
     * @var boolean
     */
    private $in_transaction = FALSE;

    /**
     * Construct this object.
     *
     * @param string $url
     *   Database URL.
     */
    function __construct($url=''){
        $this->verbose = Configuration::get('debug');

        try {
            // Extract user data.
            $results = NULL;
            preg_match('|user=(.*)[;$]|U', $url, $results);
            $username = !empty($results[1]) ? $results[1] : '';
            preg_match('|password=(.*)[;$]|U', $url, $results);
            $password = !empty($results[1]) ? $results[1] : '';

            // @todo remove @ when php header 5.6 is updated
            $this->connection = @new PDO($url, $username, $password);
        } catch (PDOException $e) {
            // Error handling.
            syslog(LOG_EMERG, 'Connection failed: ' . $e->getMessage());
            if (Configuration::get('debug')) {
                die('Connection failed: ' . $e->getMessage());
            }
            else {
                die('Connection Failed.');
            }
        }
    }

    /**
     * @return Database
     */
    public static function getInstance() {
        return parent::getInstance();
    }

    /**
     * Create a database instance with the default database.
     *
     * @return Database
     *   The database object.
     */
    public static function createInstance() {
        return new self(Configuration::get('database'));
    }

    /**
     * Set the controller to only execute select queries.
     *
     * @param boolean $value
     *   Whether read_only should be on or off.
     *
     * @notice
     *   This has no effect on direct query functions like query() and assoc()
     */
    function read_only($value = TRUE){
        $this->read_only = $value;
    }

    /**
     * Whether to enable verbose messages in output.
     *
     * @param boolean $value
     *   Whether to switch to verbose mode.
     */
    function verbose($value = TRUE){
        $this->verbose = $value;
    }

    /**
     * Outputs a list of queries that have been called during this page request.
     *
     * @return array
     */
    function get_queries(){
        return $this->history;
    }

    /**
     * Called whenever mysql returns an error executing a query.
     *
     * @param array $error
     *   The PDO error.
     * @param string $sql
     *   The original query.
     *
     * @throws Exception
     *   When a mysql error occurs.
     */
    function error_handler($error, $sql){
        $errors = array();

        // Add a header.
        $errors[] = "MYSQL ERROR ($error[0]:$error[1]): $error[2]";
        // Add the full query.
        $errors[] = $sql;

        // Show the stack trace.
        $backtrace = debug_backtrace();
        foreach($backtrace as $call){
            if(!preg_match('/class_database\.php$/', $call['file'])){
                $errors[] = 'Called from: ' .$call['file'] . ' : ' . $call['line'];
            }
        }

        // Show actual mysql error.
        $errors[] = $error[2];

        if ($this->verbose) {
            // Add a footer.
            // @todo change this so it doesn't require an input.
            foreach ($errors as $e) {
                Messenger::error($e);
            }
            throw new Exception("***** MYSQL ERROR *****");
        } else {
            foreach ($errors as $e) {
                Logger::error($e);
            }
            Logger::error($sql);
        }
        exit;
    }

    /**
     * Saves a query to the history and should be called on each query.
     *
     * @param $sql
     */
    function log($sql){
        $this->history[] = $sql;
    }

    /**
     * Start a query.
     */
    function timer_start(){
        $this->start = microtime(TRUE);
    }

    /**
     * A query is done, add up the times.
     */
    function timer_query_end(){
        $this->end1 = microtime(TRUE);
    }

    /**
     * Stop the timer and add up the times.
     */
    function timer_end(){
        if(!$this->verbose){
            $this->end2 = microtime(TRUE);
            $this->mysql_time += $this->end1-$this->start;
            $this->php_time += $this->end2-$this->start;
        }
    }

    /**
     * Reset the clock.
     */
    function timer_reset(){
        $this->query_count = 0;
        $this->mysql_time = 0;
        $this->php_time = 0;
    }

    /**
     * Output a time report
     */
    function time_report(){
        return array(
            "Total Queries: {$this->query_count}",
            "Total SQL Time: {$this->mysql_time}",
            "Total PHP Time: {$this->php_time}",
        );
    }

    /**
     * Raw query handler.
     */
    private function _query($query, $vars = array()){
        if ($this->read_only) {
            if (!preg_match("/^SELECT /i", $query)) {
                return;
            }
        }
        $this->query_count ++;
        if ($this->verbose) {
            $this->log($query);
            $this->timer_start();
            $this->__query_execute($query, $vars);
            $this->timer_query_end();
        }
        else {
            $this->__query_execute($query, $vars);
        }
        if (!$this->result) {
            $this->error_handler($this->connection->errorInfo(), $query);
        }
        elseif ($this->result->errorCode() != "00000") {
            $this->error_handler($this->result->errorInfo(), $query);
        }
    }

    /**
     * Execute query and pull results object.
     *
     * @param $query
     * @param $vars
     */
    private function __query_execute($query, $vars) {
        if (!empty($vars)) {
            $this->result = $this->connection->prepare($query);
            $this->result->execute($vars);
        }
        else {
            $this->result = $this->connection->query($query);
        }
    }

    /**
     * Returns a single cell of the first row and first column of a query.
     * For long queries, this should include a LIMIT 1
     *
     * @param $field
     * @param $sql
     * @return mixed
     */
    function field($field, $sql){
        $this->_query($sql);
        $r = $this->result->fetch(PDO::FETCH_ASSOC);
        $this->timer_end();
        return $r[$field];
    }

    /**
     * Returns a single column as an array from a query.
     *
     * @param $field
     *   The name of the column to extract as values.
     * @param $sql
     *   The query.
     * @param string $key
     *   A secondary optional column to use as the array key.
     * @return array|bool
     */
    function fields($field, $sql, $key=''){
        $this->_query($sql);
        $return = array();
        while($r = $this->result->fetch(PDO::FETCH_ASSOC)){
            if($key != '')
                $return[$r[$key]] = $r[$field];
            else
                $return[] = $r[$field];
        }
        $this->timer_end();
        if(count($return) > 0)
            return $return;
        else
            return false;
    }

    /**
     * When calling an INSERT query, this will return the last inserted auto_increment.
     *
     * @param string $sql
     *   The query with ? placeholders.
     * @param array $vars
     *   The variables to replace ?
     *
     * @return int|boolean
     *   The last insert id or FALSE.
     */
    function exec_id ($sql, $vars=array()){
        $this->_query($sql, $vars);
        $this->timer_end();
        if($this->result->rowCount() == 0)
            return false;
        else
            return $this->connection->lastInsertId();
    }

    /**
     * Simple query execution.
     *
     * @param $sql
     * @param array $vars
     *
     * @return PDOStatement
     */
    function query ($sql, $vars = array()){
        $this->_query($sql, $vars);
        $this->timer_end();
        return $this->result;
    }

    /**
     * Returns an array of arrays containing all results.
     *
     * @param $sql
     * @param string $key
     * @return array
     */
    function assoc ($sql, $key=NULL){ // RETURN ARRAY OF ALL ROWS
        $this->_query($sql);
        $array = array();
        if($this->result->rowCount() > 0){
            while($line = $this->result->fetch(PDO::FETCH_ASSOC)){
                if(!empty($key))
                    $array[$line[$key]] = $line;
                else
                    $array[] = $line;
            }
        }
        $this->timer_end();
        return $array;
    }

    /**
     * Returns an array of the first result row.
     */
    function assoc1($sql, $vars = array()){ // RETURN ONE ROW
        $this->_query($sql, $vars);
        $this->timer_end();
        return $this->result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Checks if at least one entry exists.
     */
    function check($table, $where = array()){
        $fields = empty($fields) ? '*' : implode($fields);
        $values = array();
        if (!empty($where)) {
            $where = ' WHERE ' . $this->sql_implode($where, $values, 'AND');
        }
        $this->_query('SELECT ' . $fields . ' FROM ' . $table . $where . ' LIMIT 1', $values);
        return $this->result->rowCount() > 0;
    }

    /**
     * Counts total number of matching rows.
     */
    function count($table, $where = array()){
        $fields = empty($fields) ? '*' : implode($fields);
        $values = array();
        if (!empty($where)) {
            $where = $this->sql_implode($where, $values);
        }
        $this->_query('SELECT ' . $fields . ' FROM ' . $table . $where . ' LIMIT 1', $values);
        return $this->result->rowCount();
    }

    /**
     * Update a row.
     *
     * @param $table
     * @param $data
     * @param $where
     * @return db_query
     */
    function update($table, $data, $where){
        $vars = array();
        $query = 'UPDATE ' . $table . ' SET ' . $this->sql_implode($data, $vars) . ' WHERE ';
        if (is_array($where)) {
            $query .= $this->sql_implode($where, $vars);
        }
        $this->query($query, $vars);
    }

    /**
     * Insert a new row into a table.
     *
     * @param string $table
     *   The table to insert into.
     * @param array $data
     *   An array of columns and values to set.
     * @param boolean|array $existing
     *   TRUE to ignore, an array to update.
     *
     * @return int
     *   The last inserted id.
     */
    function insert($table, $data, $existing = FALSE) {
        $vars = array();
        $ignore = $existing === TRUE ? 'IGNORE' : '';
        $set = $this->sql_implode($data, $vars);
        $duplicate = is_array($existing) ? ' ON DUPLICATE KEY UPDATE ' . $this->sql_implode($existing, $vars) : '';
        return $this->exec_id('INSERT ' . $ignore . ' INTO `' . $table . '` SET ' . $set . $duplicate, $vars);
    }

    function delete($table, $where) {
        $values = array();
        if (is_array($where)) {
            $where = $this->sql_implode($where, $values);
        }
        $this->query('DELETE FROM `' . $table . '` WHERE ' . $where, $values);
    }

    /**
     * Universal select function.
     */
    function _select($table, $where = array(), $fields = array(), $limit = NULL, $final = '') {
        if (empty($fields)) {
            $fields = '*';
        } else {
            foreach ($fields as &$field) {
                if (is_array($field)) {
                    $field = current($field) . ' AS `' . key($field) . '`';
                }
            }
            $fields = empty($fields) ? '*' : implode(', ', $fields);
        }
        $values = array();
        $where = !empty($where) ? ' WHERE ' . $this->sql_implode($where, $values, ' AND ') : '';
        $limit = is_array($limit) ? ' LIMIT ' . $limit[0] . ', ' . $limit[1] . ' '
            : !empty($limit) ? ' LIMIT ' . intval($limit) : '';
        $this->query('SELECT ' . $fields . ' FROM ' . $table . $where . ' ' . $final . $limit, $values);
    }


    /**
     * Run a select query and return a result object.
     */
    public function select($table, $where = array(), $fields = array(), $final = ''){
        $this->_select($table, $where, $fields, null, $final);
        return $this->result;
    }

    /**
     * Run a select query and return a result array.
     */
    public function selectAll($table, $where = array(), $fields = array(), $final = '') {
        return $this->select($table, $where, $fields, $final)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Run a select query and return the rows indexed by a key.
     */
    public function selectIndexed($table, $key, $where = array(), $fields = array(), $final = '') {
        $this->_select($table, $where, $fields, NULL, $final);
        $results = array();
        while ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
            $results[$row[$key]] = $row;
        }
        return $results;
    }

    /**
     * Select just a single row.
     */
    function selectRow($table, $where = array(), $fields = array(), $final = ''){
        $this->_select($table, $where, $fields, 1, $final);
        return $this->result->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Select a single column.
     *
     * @param string $table
     *   The main table to select from.
     * @param string $column
     *   The column to select.
     * @param array $where
     *   Conditions.
     * @param string $key
     *   A field to index the column.
     * @param string $final
     *   Additional query data.
     *
     * @return array
     */
    function selectColumn($table, $column, $where = array(), $key = NULL, $final = '') {
        $fields = array($column);
        if ($key) {
            array_unshift($fields, $key);
        }
        $this->_select($table, $where, $fields, NULL, $final);
        if ($key) {
            return $this->result->fetchAll(PDO::FETCH_KEY_PAIR);
        } else {
            return $this->result->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    /**
     * Select a single column from the first row.
     */
    function selectField($field, $table, $where = array(), $final = ''){
        $row = $this->selectRow($table, $where, array($field), $final);
        if (is_array($field)) {
            // This is an expression.
            reset($field);
            return $row[key($field)];
        } else {
            return $row[$field];
        }
    }

    /**
     * Gets the number of affected rows from the last query.
     *
     * @return int
     */
    function affected_rows(){
        return $this->connection->affected_rows;
    }

    /**
     * Stars a db transaction.
     */
    function start_transaction(){
        $this->query("BEGIN");
        $this->query("SET autocommit=0");
        $this->in_transaction = true;
    }

    /**
     * Ends a db transaction.
     */
    function commit_transaction(){
        $this->query("COMMIT");
        $this->query("SET autocommit=1");
        $this->in_transaction = false;
    }

    /**
     * Terminates a transaction and rolls back to the previous state.
     */
    function kill_transaction(){
        $this->query("ROLLBACK");
        $this->query("SET autocommit=1");
        $this->in_transaction = false;
    }

    /**
     * Determine if the connection is currently in a transactional state.
     *
     * @return boolean
     */
    function in_transaction(){
        return $this->in_transaction;
    }

    /**
     * Build a list of values by imploding an array.
     *
     * @param $array
     *   The field => value pairs.
     * @param $values
     *   The current list of replacement values.
     * @param string $concatenator
     *   The string used to concatenate (usually , or AND or OR)
     *
     * @return string
     *   The query string segment.
     */
    public function sql_implode($array, &$values, $concatenator=', '){
        $a2 = array();
        foreach ($array as $k=>$v) {
            if (is_array($v)) {
                if (!empty($v['expression'])) {
                    $a2[] = "`{$k}` = {$v['value']}";
                    if (!empty($v['vars']) && is_array($v['vars'])) {
                        $values = array_merge($values, $v['vars']);
                    }
                }
                // $v has more options.
                elseif (strtoupper($v[0]) == 'IN') {
                    $values = array_merge($values, array_values($v[1]));
                    $a2[] = "`{$k}` IN (" . implode(array_fill(0, count($v[1]), '?'), ",") . ")";
                }
                elseif (strtoupper($v[0]) == 'BETWEEN') {
                    $a2[] = "`{$k}` BETWEEN ? AND ? ";
                    $values[] = $v[1];
                    $values[] = $v[2];
                }
                elseif (in_array($v[0], array('!=', '<', '<=', '>', '>=', 'LIKE'))) {
                    $values[] = $v[1];
                    $a2[] = " `{$k}` {$v[0]} ? ";
                }
            }
            else {
                // String or numeric simple.
                $values[] = $v;
                $a2[] = " `{$k}` = ? ";
            }
        }
        return implode($concatenator, $a2);
    }

    public function createTable($table, $columns, $indexes) {
        $primary_added = false;

        // Find the primary column if there is only 1.
        $primary_column = null;
        if (empty($indexes['primary'])) {
            $primary_column = null;
        }
        if (is_string($indexes['primary'])) {
            $primary_column = $indexes['primary'];
        }
        elseif (!empty($indexes['primary']['columns'])) {
            if (count($indexes['primary']['columns']) == 1) {
                $primary_column = $indexes['primary']['columns'][0];
            }
        }

        foreach ($columns as $column => $settings) {
            $definitions[] = $this->getColumnDefinition($column, $settings, $primary_column == $column);
            if ($primary_column == $column) {
                $primary_added = true;
            }
        }

        foreach ($indexes as $index => $settings) {
            if ($primary_added && $index == 'primary') {
                // The primary key was already added with the column.
                continue;
            }
            $definitions[] = $this->getIndexDefinition($index, $settings);
        }

        $query = "CREATE TABLE {$table} (" . implode(',', $definitions) . ') ENGINE=InnoDB;';

        $this->query($query);
    }

    protected function getColumnDefinition($name, $settings, $primary = false) {
        $definition = "`{$name}` ";

        $definition .= $settings['type'];
        if (!empty($settings['size'])) {
            $definition .= "({$settings['size']})";
        }

        if (!empty($settings['unsigned'])) {
            $definition .= ' UNSIGNED ';
        }

        if (empty($settings['null'])) {
            $definition .= ' NOT NULL ';
        } else {
            $definition .= ' NULL ';
        }

        if (!empty($settings['auto_increment']) || $primary) {
            $definition .= ' PRIMARY KEY ';

            if (!empty($settings['auto_increment'])) {
                $definition .= 'AUTO_INCREMENT';
            }
        }

        return $definition;
    }

    protected function getIndexDefinition($name, $settings) {
        // Figure out the columns.
        if (is_array($settings['columns'])) {
            $columns = $settings['columns'];
        }
        elseif (is_string($settings['columns'])) {
            $columns = array($settings['columns']);
        }
        else {
            $columns = array($name);
        }

        $definition = empty($settings['unique']) ? 'INDEX ' : 'UNIQUE INDEX ';
        $definition .= '`' . $name . '` (`' . implode('`,`', $columns) . '`)' ;
        if (!empty($settings['size'])) {
            $definition .= ' KEY_BLOCK_SIZE = ' . intval($settings['size']);
        }
        return $definition;
    }

    /**
     * Check if a table exists.
     *
     * @param string $table
     *   The name of the table.
     *
     * @return boolean
     */
    public function tableExists($table) {
        return $this->query('SHOW TABLES LIKE ?', array($table))->rowCount() == 1;
    }
}
