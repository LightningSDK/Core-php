<?php
/**
 * @file
 * Contains lightningsdk\core\Tools\API\Server
 */

namespace lightningsdk\core\Tools\Communicator;
use Exception;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Logger;
use lightningsdk\core\Tools\Messenger;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\Tools\Request;
use lightningsdk\core\View\API;

/**
 * A light weight API interface.
 *
 * @package lightningsdk\core\Tools\API
 */
class Server extends API {

    protected $action_namespace;
    protected $output;
    protected $verbose = false;
    protected $shutdown_function = '';

    /**
     * Initialize the object.
     */
    public function __construct() {
        parent::__construct();
        $this->action_namespace = Configuration::get('communicator.server.action_namespace', 'Source\\Actions');
        $this->verbose = Configuration::get('communicator.server.debug', false);
        $this->shutdown_function = Configuration::get('communicator.server.shutdown_function');
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
            list ($class, $action) = explode('.', $action);
            try{
                $class = $this->action_namespace . '\\' . $class;
                $controller = new $class();
                $controller->$action($this);
            } catch(Exception $e) {
                $this->handleException($e);
            }
        }
    }

    protected function handleException($exception) {
        Logger::exception($exception);
        Messenger::error($exception->getMessage());
    }

    protected function loadAddtionalData($data) {
        foreach($data as $d) {
            try{
                $class = $this->action_namespace . '\\Load';
                $controller = new $class();
                if ($result = $controller->$d($this)) {
                    // The call can update the output or return the value.
                    $this->$d = $result;
                }
            } catch(Exception $e) {
                Messenger::error($this->verbose ? $e->getMessage() : 'There was an error processing your request.');
            }
        }
    }

    protected function finalize() {

    }
}
