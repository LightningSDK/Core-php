<?php
/**
 * @file
 * Lightning\Tools\Database
 */

namespace Lightning\Tools;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use ReflectionClass;

/**
 * A database abstraction layer.
 *
 * @package Lightning\Tools
 */
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
     * The last query executed. If it's the same it does not need to be reprepared.
     *
     * @var string
     */
    private $last_query;

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
    private $readOnly = FALSE;

    /**
     * Whether the current connection is in the transaction state.
     *
     * @var boolean
     */
    private $inTransaction = FALSE;

    const FETCH_ASSOC = PDO::FETCH_ASSOC;

    /**
     * Construct this object.
     *
     * @param string $url
     *   Database URL.
     */
    public function __construct($url=''){
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
            if ($this->verbose) {
                die('Connection failed: ' . $e->getMessage());
            }
            else {
                die('Connection Failed.');
            }
        }
    }

    /**
     * Get the default database instance.
     *
     * @return Database
     *   The singleton Database object.
     */
    public static function getInstance($create = true) {
        return parent::getInstance($create);
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
     *   Whether readOnly should be on or off.
     *
     * @notice
     *   This has no effect on direct query functions like query() and assoc()
     */
    public function readOnly($value = TRUE){
        $this->readOnly = $value;
    }

    /**
     * Whether to enable verbose messages in output.
     *
     * @param boolean $value
     *   Whether to switch to verbose mode.
     */
    public function verbose($value = TRUE){
        $this->verbose = $value;
    }

    /**
     * Outputs a list of queries that have been called during this page request.
     *
     * @return array
     *   A list of executed queries.
     */
    public function getQueries(){
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
    public function errorHandler($error, $sql){
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
     *   Add a query to the sql log.
     */
    public function log($sql, $vars, $time){
        $this->history[] = array(
            'query' => $sql,
            'vars' => $vars,
            'time' => $time,
        );
    }

    /**
     * Start a query.
     */
    public function timerStart(){
        $this->start = microtime(TRUE);
    }

    /**
     * A query is done, add up the times.
     */
    public function timerQueryEnd(){
        $this->end1 = microtime(TRUE);
    }

    /**
     * Stop the timer and add up the times.
     */
    public function timerEnd(){
        $this->end2 = microtime(TRUE);
        $this->mysql_time += $this->end1-$this->start;
        $this->php_time += $this->end2-$this->start;
    }

    /**
     * Reset the clock.
     */
    public function timerReset(){
        $this->query_count = 0;
        $this->mysql_time = 0;
        $this->php_time = 0;
    }

    /**
     * Output a time report
     */
    public function timeReport(){
        return array(
            "Total Queries: {$this->query_count}",
            "Total SQL Time: {$this->mysql_time}",
            "Total PHP Time: {$this->php_time}",
        );
    }

    /**
     * Raw query handler.
     *
     * @param string $query
     *   The rendered query.
     * @param array $vars
     *   A list of replacement variables.
     */
    private function _query($query, $vars = array()){
        if ($this->readOnly) {
            if (!preg_match("/^SELECT /i", $query)) {
                return;
            }
        }
        $this->query_count ++;
        if ($this->verbose) {
            $this->timerStart();
            $this->__query_execute($query, $vars);
            $this->timerQueryEnd();
            $this->log($query, $vars, $this->end1 - $this->start);
        }
        else {
            $this->__query_execute($query, $vars);
        }
        if (!$this->result) {
            $this->errorHandler($this->connection->errorInfo(), $query);
        }
        elseif ($this->result->errorCode() != "00000") {
            $this->errorHandler($this->result->errorInfo(), $query);
        }
    }

    /**
     * Execute query and pull results object.
     *
     * @param string $query
     *   The rendered query.
     * @param array $vars
     *   A list of replacement variables.
     */
    private function __query_execute($query, $vars) {
        // If the query has changed, we need to prepare a new one.
        if ($this->last_query != $query) {
            $this->last_query = $query;
            $this->result = $this->connection->prepare($query);
        }
        // Execute the query with subsitutions.
        $this->result->execute($vars);
    }

    /**
     * Simple query execution.
     *
     * @param string $query
     *   The rendered query.
     * @param array $vars
     *   A list of replacement variables.
     *
     * @return PDOStatement
     */
    public function query($query, $vars = array()){
        $this->_query($query, $vars);
        $this->timerEnd();
        return $this->result;
    }

    /**
     * Checks if at least one entry exists.
     *
     * @param array|string $table
     *   The table and optionally joins.
     * @param array $where
     *   A list of conditions for the query.
     *
     * @return boolean
     *   Whether there is at least one matching entry.
     */
    public function check($table, $where = array()){
        $fields = empty($fields) ? '*' : implode($fields);
        $values = array();
        $where = empty($where) ? '' : ' WHERE ' . $this->sqlImplode($where, $values, 'AND');
        $this->query('SELECT ' . $fields . ' FROM ' . $this->parseTable($table, $values) . $where . ' LIMIT 1', $values);
        return $this->result->rowCount() > 0;
    }

    /**
     * Counts total number of matching rows.
     *
     * @param array|string $table
     *   The table and optionally joins.
     * @param array $where
     *   A list of conditions for the query.
     *
     * @return integer
     *   How many matching rows were found.
     */
    public function count($table, $where = array(), $count_field = '*'){
        return (integer) $this->selectField(array('count' => array('expression' => 'COUNT(' . $count_field . ')')), $table, $where);
    }

    /**
     * Get a list of counted groups, keyed by an index.
     *
     * @param string $table
     *   The table to search.
     * @param string $key
     *   The table to use as the key.
     * @param array $where
     *   A list of conditions for the query.
     * @param string $order
     *   Additional order information.
     *
     * @return array
     *   A list of counts keyed by the $key column.
     */
    public function countKeyed($table, $key, $where = array(), $order = ''){
        $this->_select($table, $where, array('count' => array('expression' => 'COUNT(*)'), $key), NULL, 'GROUP BY `' . $key . '` ' . $order);
        $results = array();
        // TODO: This is built in to PDO.
        while ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
            $results[$row[$key]] = $row['count'];
        }
        $this->timerEnd();
        return $results;
    }

    /**
     * Update a row.
     *
     * @param string $table
     *   The table to update.
     * @param array $data
     *   A list of new values keyed by the column.
     * @param array $where
     *   A list of conditions on which rows to update.
     *
     * @return integer
     *   The number of rows updated.
     */
    public function update($table, $data, $where){
        $vars = array();
        $query = 'UPDATE ' . $this->parseTable($table, $vars) . ' SET ' . $this->sqlImplode($data, $vars, ', ', true) . ' WHERE ';
        if (is_array($where)) {
            $query .= $this->sqlImplode($where, $vars, ' AND ');
        }
        $this->query($query, $vars);
        $this->timerEnd();
        return $this->result->rowCount() == 0 ? false : $this->connection->lastInsertId();
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
     * @return integer
     *   The last inserted id.
     */
    public function insert($table, $data, $existing = FALSE) {
        $vars = array();
        $table = $this->parseTable($table, $vars);
        $ignore = $existing === TRUE ? 'IGNORE' : '';
        $set = $this->sqlImplode($data, $vars, ', ', true);
        $duplicate = is_array($existing) ? ' ON DUPLICATE KEY UPDATE ' . $this->sqlImplode($existing, $vars) : '';
        $this->query('INSERT ' . $ignore . ' INTO ' . $table . ' SET ' . $set . $duplicate, $vars);
        $this->timerEnd();
        return $this->result->rowCount() == 0 ? false :
            // If there is no auto increment, just return true.
            ($this->connection->lastInsertId() ?: true);
    }

    /**
     * Insert multiple values for each combination of the supplied data values.
     *
     * @param array $table
     *   The table to insert to.
     * @param array $data
     *   An array where each item corresponds to a column.
     *   The value may be a string or an array of multiple values.
     * @param array|boolean $existing
     *   TRUE to ignore, or an array of field names from which to copy
     *   update the value with the value from $data if the unique key exists.
     *
     * @return integer
     *   The last inserted id.
     */
    public function insertMultiple($table, $data, $existing = FALSE) {
        $last_insert = false;

        // Set up the constant variables.
        $start_vars = array();
        $table = $this->parseTable($table, $start_vars);
        $ignore = $existing === TRUE ? 'IGNORE' : '';

        // This passes $data as individual params to the __construct() function.
        $reflect  = new ReflectionClass('Lightning\Tools\CombinationIterator');
        $combinator = $reflect->newInstanceArgs($data);

        $fields = $this->implodeFields(array_keys($data));
        $placeholder_set = '(' . implode(',', array_fill(0, count($data), '?')) . ')';

        // Add the update on existing key.
        $duplicate = '';
        if (is_array($existing)) {
            $duplicate .= ' ON DUPLICATE KEY UPDATE ';
            $feilds = array();
            foreach ($existing as $field) {
                $feilds[] = '`' . $field . '`=VALUES(`' . $field . '`)';
            }
            $duplicate .= implode(',', $fields);
        }

        // Initialize data.
        $vars = array();
        $i = 0;
        $iterations_per_query = 100;

        // Iterate over each value combination.
        foreach ($combinator as $combination) {
            $i++;
            // If ($iterations_per_query) have already been inserted, reset to a new query.
            if ($i > $iterations_per_query) {
                if (empty($values)) {
                    $values = implode(',', array_fill(0, $iterations_per_query, $placeholder_set));
                }
                $this->query('INSERT ' . $ignore . ' INTO ' . $table . '(' . $fields . ') VALUES ' . $values . $duplicate, $vars);
                $last_insert = $this->result->rowCount() == 0 ? $last_insert : $this->connection->lastInsertId();
                // Reset the data.
                $i = 1;
                $vars = array();
            }
            $vars = array_merge($vars, $combination);
        }

        // The placeholder count might be different.
        if (empty($values) || (count($vars) / count($data) != $iterations_per_query)) {
            $values = implode(',', array_fill(0, count($vars) / count($data), $placeholder_set));
        }

        // Run the insert query for remaining sets.
        $this->query('INSERT ' . $ignore . ' INTO ' . $table . '(' . $fields . ') VALUES ' . $values . $duplicate, $vars);

        // Return the last insert ID.
        return $this->result->rowCount() == 0 ? $last_insert : $this->connection->lastInsertId();
    }

    /**
     * Delete rows from the database.
     *
     * @param string $table
     *   The table to delete from.
     * @param array $where
     *   The condition for the query.
     *
     * @return integer
     *   The number of rows deleted.
     */
    public function delete($table, $where) {
        $values = array();
        $table = $this->parseTable($table, $values);
        if (is_array($where)) {
            $where = $this->sqlImplode($where, $values, ' AND ');
        }
        $this->query('DELETE FROM ' . $table . ' WHERE ' . $where, $values);

        return $this->result->rowCount() ?: false;
    }

    /**
     * Universal select function.
     *
     * @param array|string $table
     *   The table and optionally joins.
     * @param array $where
     *   A list of conditions for the query.
     * @param array $fields
     *   A list of fields to select.
     * @param array|integer $limit
     *   A limited number of rows.
     * @param string $final
     *   A final string to append to the query, such as limit and sort.
     */
    protected function _select($table, $where = array(), $fields = array(), $limit = NULL, $final = '') {
        $fields = $this->implodeFields($fields);
        $values = array();
        if (!empty($where) && $where = $this->sqlImplode($where, $values, ' AND ')) {
            $where = ' WHERE ' . $where;
        } else {
            $where = '';
        }
        $limit = is_array($limit) ? ' LIMIT ' . $limit[0] . ', ' . $limit[1] . ' '
            : !empty($limit) ? ' LIMIT ' . intval($limit) : '';
        $table_values = array();
        $table = $this->parseTable($table, $table_values);
        $this->query('SELECT ' . $fields . ' FROM ' . $table . $where . ' ' . $final . $limit, array_merge($table_values, $values));
    }

    /**
     * Experimental:
     * This should parse the entire query provided as an array.
     *
     * @param array $query
     *   The query to run.
     * @param array $values
     *   Empty array for new values.
     *
     * @return string
     *   The built query.
     *
     * @todo This should be protected.
     */
    public function parseQuery($query, &$values, $type = 'SELECT') {
        $output = $type . ' ';
        if ($type == 'SELECT') {
            $output .= $this->implodeFields(!empty($query['select']) ? $query['select'] : '*');
        }
        if (!empty($query['from'])) {
            $output .= ' FROM ' . $this->parseTable($query['from'], $values);
        }
        if (!empty($query['join'])) {
            $output .= $this->parseJoin($query['join'], $values);
        }
        if (!empty($query['where'])) {
            $output .= ' WHERE ' . $this->sqlImplode($query['where'], $values, ' AND ');
        }
        if (!empty($query['group_by'])) {
            $output .= ' GROUP BY ' . $this->implodeFields($query['group_by']);
        }
        if (!empty($query['order_by'])) {
            $output .= ' ORDER BY ' . $this->sqlImplode($query['order_by'][0], $values) . ' ' . $query['order_by'][1];
        }

        return $output;
    }

    /**
     * Parse join data into a query string.
     *
     * @param array $join
     *   The join data.
     * @param array $values
     *   The array to add variables to.
     *
     * @return string
     *   The rendered query portion.
     */
    protected function parseJoin($join, &$values) {
        // If the first element of join is not an array, it's an actual join.
        if (!is_array(current($join))) {
            // Wrap it in an array so we can loop over it.
            $join = array($join);
        }
        // Foreach join.
        $output = '';
        foreach ($join as $alias => $join) {
            $output .= $this->implodeJoin($join[0], $join[1], !empty($join[2]) ? $join[2] : '', $values, is_string($alias) ? $alias : null);
            // Add any extra replacement variables.
            if (isset($join[3])) {
                $values = array_merge($values, $join[3]);
            }
        }
        return $output;
    }

    /**
     * Create a query-ready string for a table and it's joins.
     *
     * @param string|array $table
     *   The table name or table with join data.
     * @param array $values
     *   The PDO replacement variables.
     *
     * @return string
     *   The query-ready string for the table and it's joins.
     */
    protected function parseTable($table, &$values, $alias = null) {
        if (is_string($table)) {
            // A simple table as alias.
            $output = '`' . $table . '`';
            if (!empty($alias)) {
                $output .= 'AS `' . $alias . '`';
            }
            return $output;
        }
        else {
            if (!empty($table['from'])) {
                $output = $this->parseTable($table['from'], $values);
            }
            if (!empty($table['join'])) {
                $output .= $this->parseJoin($table['join'], $values);
            }
            // If this join is a subquery, wrap it.
            if (is_array($table)) {
                if (isset($table['as'])) {
                    if (!empty($table['fields'])) {
                        $output = $this->implodeFields($table['fields']) . ' FROM ' . $output;
                    } else {
                        $output = ' * FROM ' . $output;
                    }
                    if (!empty($table['order'])) {
                        $output .= $this->implodeOrder($table['order'], $values);
                    }
                    $output = '( SELECT ' . $output . ') AS ' . $table['as'];
                } elseif (count($table) == 1 && empty($table['from'])) {
                    $alias = key($table);
                    $table = current($table);
                    if (is_array($table)) {
                        // This must be the new experimental format.
                        $output = '(' . $this->parseQuery($table, $values) . ') AS `' . $alias . '`';
                    } else {
                        $output = '`' . $table . '` AS `' . $alias . '`';
                    }
                }
            }
            return $output;
        }
    }

    /**
     * Run a select query and return a result object.
     *
     * @param array|string $table
     *   The table and optionally joins.
     * @param array $where
     *   A list of conditions for the query.
     * @param array $fields
     *   A list of fields to select.
     * @param string $final
     *   A final string to append to the query, such as limit and sort.
     *
     * @return PDOStatement
     *   The query results.
     */
    public function select($table, $where = array(), $fields = array(), $final = ''){
        $this->_select($table, $where, $fields, null, $final);
        $this->timerEnd();
        return $this->result;
    }

    /**
     * Run a select query and return a result array.
     *
     * @param array|string $table
     *   The table and optionally joins.
     * @param array $where
     *   A list of conditions for the query.
     * @param array $fields
     *   A list of fields to select.
     * @param string $final
     *   A final string to append to the query, such as limit and sort.
     *
     * @return array
     *   The query results.
     */
    public function selectAll($table, $where = array(), $fields = array(), $final = '') {
        $this->_select($table, $where, $fields, null, $final);
        $result = $this->result->fetchAll(PDO::FETCH_ASSOC);
        $this->timerEnd();
        return $result;
    }

    /**
     * Run a select query and return the rows indexed by a key.
     *
     * @param array|string $table
     *   The table and optionally joins.
     * @param string $key
     *   The column to use as the array index.
     * @param array $where
     *   A list of conditions for the query.
     * @param array $fields
     *   A list of fields to select.
     * @param string $final
     *   A final string to append to the query, such as limit and sort.
     *
     * @return array
     *   The query results keyed by $key.
     */
    public function selectIndexed($table, $key, $where = array(), $fields = array(), $final = '') {
        $this->_select($table, $where, $fields, NULL, $final);
        $results = array();
        // TODO: This is built in to PDO.
        while ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
            $results[$row[$key]] = $row;
        }
        $this->timerEnd();
        return $results;
    }

    /**
     * Select just a single row.
     *
     * @param array|string $table
     *   The table and optionally joins.
     * @param array $where
     *   A list of conditions for the query.
     * @param array $fields
     *   A list of fields to select.
     * @param string $final
     *   A final string to append to the query, such as limit and sort.
     *
     * @return array
     *   A single row from the database.
     */
    public function selectRow($table, $where = array(), $fields = array(), $final = ''){
        $this->_select($table, $where, $fields, 1, $final);
        $this->timerEnd();
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
     *   All values from the column.
     */
    public function selectColumn($table, $column, $where = array(), $key = NULL, $final = '') {
        $fields = array($column);
        if ($key) {
            array_unshift($fields, $key);
        }
        $this->_select($table, $where, $fields, NULL, $final);
        if ($key) {
            $output = $this->result->fetchAll(PDO::FETCH_KEY_PAIR);
        } else {
            $output = $this->result->fetchAll(PDO::FETCH_COLUMN);
        }
        $this->timerEnd();
        return $output;
    }

    /**
     * Select a single column from the first row.
     *
     * @param string $field
     *   The column to select from.
     * @param array|string $table
     *   The table and optionally joins.
     * @param array $where
     *   A list of conditions for the query.
     * @param string $final
     *   A final string to append to the query, such as limit and sort.
     *
     * @return mixed
     *   A single field value.
     */
    public function selectField($field, $table, $where = array(), $final = ''){
        if (!is_array($field)) {
            $field = array($field => $field);
        }
        $row = $this->selectRow($table, $where, $field, $final);

        reset($field);
        return $row[key($field)];
    }

    /**
     * Gets the number of affected rows from the last query.
     *
     * @return integer
     *   The number of rows that were affected.
     */
    public function affectedRows(){
        return $this->result->rowCount();
    }

    /**
     * Stars a db transaction.
     */
    public function startTransaction(){
        $this->query("BEGIN");
        $this->query("SET autocommit=0");
        $this->inTransaction = true;
    }

    /**
     * Ends a db transaction.
     */
    public function commitTransaction(){
        $this->query("COMMIT");
        $this->query("SET autocommit=1");
        $this->inTransaction = false;
    }

    /**
     * Terminates a transaction and rolls back to the previous state.
     */
    public function killTransaction(){
        $this->query("ROLLBACK");
        $this->query("SET autocommit=1");
        $this->inTransaction = false;
    }

    /**
     * Determine if the connection is currently in a transactional state.
     *
     * @return boolean
     *   Whther the current connection is in a transaction.
     */
    public function inTransaction(){
        return $this->inTransaction;
    }

    /**
     * Convert an order array into a query string.
     *
     * @param array $order
     *   A list of fields and their order.
     *
     * @return string
     *   SQL ready string.
     */
    protected function implodeOrder($order) {
        $output = ' ORDER BY ';
        foreach ($order as $field => $direction) {
            $output .= '`' . $field . '` ' . $direction;
        }
        return $output;
    }

    /**
     * Implode a join from the name, table, condition, etc.
     *
     * @param string $joinType
     *   LEFT JOIN, JOIN, RIGHT JOIN, INNER JOIN
     * @param string|array $table
     *   The table criteria
     * @param string $condition
     *   Including USING or ON
     * @param array $values
     *   The PDO replacement variables.
     *
     * @return string
     *   The SQL query segment.
     */
    protected function implodeJoin($joinType, $table, $condition, &$values, $alias = null) {
        return ' ' . $joinType . ' ' . $this->parseTable($table, $values, $alias) . ' ' . $condition;
    }

    /**
     * Convert a list of fields into a string.
     *
     * @param array $fields
     *   A list of fields and their aliases to retrieve.
     *
     * @return string
     *   The SQL query segment.
     */
    protected function implodeFields($fields) {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        foreach ($fields as $alias => &$field) {
            $current = null;
            if (is_array($field)) {
                $current = current($field);
            }
            if (!empty($current) && !empty($field['expression'])) {
                // Format of array('count' => array('expression' => 'COUNT(*)'))
                $field = $field['expression'] . ' AS ' . $this->formatField($alias);
            }
            elseif (!empty($field) && is_array($field)) {
                // Format of array('table' => array('column1', 'column2'))
                // Or array('table' => array('alias' => 'column'))
                // Or array(0 => array('table' => array('column1', 'alias' => 'column2')))
                $table = $alias;
                $table_field_list = $this->implodeTableFields($table, $field);
                $field = implode(', ', $table_field_list);
            }
            else {
                if (!empty($current)) {
                    $alias = key($field);
                    $field = $current;
                }
                $field = $this->formatField($field);

                if (!empty($alias) && !is_numeric($alias)) {
                    // Format of array('alias' => 'column') to column as `alias`.
                    $field = $field . ' AS `' . $alias . '`';
                }
            }
        }
        return empty($fields) ? '*' : implode(', ', $fields);
    }

    /**
     * Implode tables and fields wrapped in an array.
     *
     * @param string $first
     *   The first param, either the table name or an unused index.
     * @param array $seconds
     *   The sub fields of the table.
     *
     * @return array
     *   A list of formatted fields.
     */
    protected function implodeTableFields($first, $seconds) {
        $output = array();
        foreach ($seconds as $second => $third) {
            if (is_array($third)) {
                // array(0 => array('table' => array('column1', 'alias' => 'column2')))
                $output = array_merge($output, $this->implodeTableFields($second, $third));
            } elseif (is_numeric($second)) {
                // Format of array('table' => array('column'))
                if ($third == '*') {
                    $output[] = "`{$first}`.*";
                } else {
                    $output[] = "`{$first}`.`{$third}`";
                }
            } else {
                // Format of array('table' => array('alias' => 'column'))
                $output[] = "`{$first}`.`{$third}` AS `{$second}`";
            }
        }
        return $output;
    }

    /**
     * Convert a field to a valid SQL reference.
     *
     * @param string $field
     *   The field as submitted in the query.
     *
     * @return string
     *   The field ready for SQL.
     */
    protected function formatField($field) {
        $table = '';

        // Add the table if there is one.
        $field = explode('.', $field);
        if (count($field) == 1) {
            $field = $field[0];
        } elseif (count($field)  == 2) {
            $table = '`' . $field[0] . '`.';
            $field = $field[1];
        }

        // Add the field.
        if ($field == '*') {
            return $table . '*';
        } else{
            return $table . '`' . $field . '`';
        }
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
     * @param boolean $setting
     *   If we are setting variables. (Helps in determining what to do with null values)
     *
     * @return string
     *   The query string segment.
     */
    public function sqlImplode($array, &$values, $concatenator = ', ', $setting = false){
        $a2 = array();
        if (!is_array($array)) {
            $array = array($array);
        }
        foreach ($array as $field => $v) {
            if (is_numeric($field) && empty($v['expression'])) {
                if ($subImplode = $this->sqlImplode($v, $values, ' AND ')) {
                    $a2[] = $subImplode;
                }
            }

            // This might change from an and to an or.
            if ($field === '#operator') {
                $concatenator = $v;
                continue;
            }
            // This is if and AND/OR is explicitly grouped.
            elseif (($field === '#OR' || $field === '#AND') && !empty($v)) {
                if ($subImplode = $this->sqlImplode($v, $values, ' ' . str_replace('#', '', $field) . ' ')) {
                    $a2[] = '(' . $subImplode . ')';
                }
                continue;
            }

            if (is_string($field)) {
                $field = $this->formatField($field);
            }

            // If the value is an array.
            if (is_array($v)) {
                // Value is an expression.
                if (!empty($v['expression'])) {
                    if (is_numeric($field)) {
                        // There is no name, this expression should contain it's own equations.
                        $a2[] = $v['expression'];
                    } else {
                        // Check a field equal to an expression.
                        $a2[] = "{$field} = {$v['expression']}";
                    }
                    // Add any vars.
                    if (!empty($v['vars']) && is_array($v['vars'])) {
                        $values = array_merge($values, $v['vars']);
                    }
                }
                // IN operator.
                elseif (!empty($v[0])){
                    switch (strtoupper($v[0])) {
                        case 'IN':
                            // The IN list is empty, so the set should be empty.
                            if (empty($v[1])) {
                                $a2[] = 'false';
                                break;
                            }
                        case 'NOT IN':
                            // The NOT IN list is empty, all results apply.
                            // Add the IN or NOT IN query.
                            if (empty($v[1])) {
                                break;
                            }
                            $values = array_merge($values, array_values($v[1]));
                            $a2[] = "{$field} {$v[0]} (" . implode(array_fill(0, count($v[1]), '?'), ",") . ")";
                            break;
                        case 'BETWEEN':
                            $a2[] = "{$field} BETWEEN ? AND ? ";
                            $values[] = $v[1];
                            $values[] = $v[2];
                            break;
                        case 'IS NULL':
                        case 'IS NOT NULL':
                            $a2[] = "{$field} {$v[0]} ";
                            break;
                        case '!=':
                        case '<':
                        case '<=':
                        case '>':
                        case '>=':
                        case 'LIKE':
                            $values[] = $v[1];
                            $a2[] = "{$field} {$v[0]} ? ";
                            break;
                    }
                }
            }
            elseif ($v === null) {
                if ($setting) {
                    $a2[] = "{$field} = NULL ";
                } else {
                    $a2[] = "{$field} IS NULL ";
                }
            }
            else {
                // Standard key/value column = value.
                $values[] = $v;
                $a2[] = "{$field} = ? ";
            }
        }
        return implode($concatenator, $a2);
    }

    /**
     * Create a new table.
     *
     * @param string $table
     *   The table name.
     * @param array $columns
     *   The columns to add.
     * @param array $indexes
     *   The indexes to add.
     */
    public function createTable($table, $columns, $indexes) {
        $primary_added = false;

        // Find the primary column if there is only 1.
        $primary_column = null;
        if (empty($indexes['primary'])) {
            $primary_column = null;
        }
        if (!empty($indexes['primary']) && is_string($indexes['primary'])) {
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

    /**
     * Create a column definition for adding to a table.
     *
     * @param string $name
     *   The name of the column.
     * @param array $settings
     *   The definition of the column.
     * @param boolean $primary
     *   Whether this column should be the primary key.
     *
     * @return string
     *   The column definition.
     */
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

    /**
     * Create an index definition to add to a table.
     *
     * @param string $name
     *   The index name.
     * @param array $settings
     *   The index definition.
     *
     * @return string
     *   The index definition.
     */
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

        if ($name == 'primary') {
            $definition = 'PRIMARY KEY ';
        } else {
            $definition = (empty($settings['unique']) ? 'INDEX ' : 'UNIQUE INDEX ') . '`' . $name . '`';
        }
        $definition .= ' (`' . implode('`,`', $columns) . '`)' ;
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
