<?php
/**
 * @file
 * Contains Lightning\Tools\API\Client
 */

namespace Lightning\Tools\Communicator;
use Exception;
use Lightning\Tools\Messenger;
use Lightning\Tools\Navigation;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;

/**
 * Makes a fast call to an API server using curl
 *
 * @package Lightning\Tools\API
 */
class Client extends RestClient {

    protected $vars = array('actions' => array());

    protected $load = array();

    /**
     * Gets a var from the returned data.
     *
     * Communicator data is wrapped in a 'data' field.
     *
     * @param $var
     * @return null
     */
    function get($var) {
        if (isset($this->results['data'][$var]))
            return $this->results['data'][$var];
        return NULL;
    }

    /**
     * If there was an action called at execution time, this will show it.
     */
    function print_last_action() {
        if ($this->last_action) { echo $this->last_action; }
    }

    /**
     * Adds a request for additional data.
     * @todo - can this be replaced now that action is an array?
     *
     * @param $var
     */
    public function load($var) {
        if (is_array($var)) {
            $this->load = array_merge($this->load, $var);
        } else {
            $this->load[] = $var;
        }
    }

    /**
     * Connect and interpret response.
     *
     * @param null $action
     *   Shortcut to add an additional action to post at call time.
     * @return bool
     *   Returns true if no errors from the communicator server.
     */
    public function call($action=NULL) {
        try {
            // Compose all vars.
            $vars = $this->vars;
            // set the action
            if ($action) {
                $vars['actions'][] = $action;
                $this->last_action = $action;
            }
            // Request additional data.
            $vars['load'] = $this->load;

            // Connect to server.
            $this->connect($vars);

            return $this->processResponse();
        } catch (Exception $e) {
            print_r($e->getMessage());
            $this->_die("There was an error processing your request. Please try again later. (2)");
        }
    }


    public function getAdditionalData() {
        return $this->load;
    }

}
