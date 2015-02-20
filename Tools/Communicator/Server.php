<?php
/**
 * @file
 * Contains Lightning\Tools\API\Server
 */

namespace Lightning\Tools\Communicator;
use Exception;
use Lightning\Tools\Configuration;
use Lightning\Tools\Messenger;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\View\API;

/**
 * A light weight API interface.
 *
 * @package Lightning\Tools\API
 */
class Server extends API {

    protected $action_namespace;
    protected $output = array();
    protected $verbose = false;
    protected $shutdown_function = '';

    /**
     * Initialize the object.
     */
    public function __construct() {
        parent::__construct();
        $this->action_namespace = Configuration::get('communicator.server.action_namespace', 'Source\\Actions');
        $this->verbose = Configuration::get('communicator.server.debug', false);
        $this->shutdown_function = Configuration::get('communicator.server.debug');
    }

    public function __set($var, $value) {
        $this->output[$var] = $value;
    }

    /**
     * Perform request from client.
     */
    public function execute() {
        // TODO Check for an authentication key if required.

        // Perform requested actions.
        if ($actions = Request::get('actions', 'array')) {
            $this->executeActions($actions);
        }
        if ($load = Request::get('load', 'array')) {
            $this->loadAddtionalData($load);
        }

        $this->finalize();
        Output::jsonData($this->output, true);
    }

    protected function executeActions($actions) {
        foreach($actions as $action) {
            list ($class, $action) = explode(".",$action);
            try{
                $class = $this->action_namespace . '\\' . $class;
                $controller = new $class();
                $controller->$action($this);
            } catch(Exception $e) {
                $this->_die($e->getMessage());
            }
        }
    }

    protected function loadAddtionalData($data) {
        foreach($data as $d) {
            try{
                $class = $this->action_namespace . '\\Load';
                $controller = new $class();
                $this->$d = $controller->$d($this);
            } catch(Exception $e) {
                $this->_die($e->getMessage());
            }
        }
    }

    /**
     * Terminate the program and send any current errors or messages.
     *
     * @param string $error
     *   An optional error message to add at fail time.
     */
    protected function _die($error='') {
        // These must be global to send to the foot file.
        // @todo fire some final callback

        if ($this->verbose) {
            Messenger::error($error);
        }

        // Call the shutdown function.
        if (!empty($this->shutdown_function) && is_callable($this->shutdown_function)) {
            call_user_func($this->shutdown_function, $this->output, FALSE, FALSE);
        }

        $this->finalize();
        Output::jsonData($this->output);
    }

    protected function finalize() {

    }
}
