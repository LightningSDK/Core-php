<?php
/**
 * @file
 * Contains Lightning\Tools\API\Server
 */

namespace Lightning\Tools\API;
use Lightning\Tools\Messenger;
use Lightning\Tools\Request;

/**
 * A light weight API interface.
 *
 * @package Lightning\Tools\API
 */
class Server {

    private $action_path;
    private $auth_key;
    private $messages;
    private $errors = array();
    private $output;
    private $debug = FALSE;
    private $shutdown_function = '';

    /**
     * Initialize the object.
     *
     * @param string $action_path
     *   The location of the action classes.
     * @param string $auth_key
     *   An authentication key.
     * @param string|callable $shutdown_function
     *   The function to call before shutting down.
     */
    function __construct($action_path, $auth_key = '', $shutdown_function = ''){
        $this->action_path = $action_path;
        $this->auth_key = $auth_key;
        $this->shutdown_function = $shutdown_function;
    }

    /**
     * Sets the debug mode.
     *
     * @param boolean $debug
     *   Whether to set the debug mode on or off.
     */
    function debug($debug = true){
        $this->debug = $debug;
    }

    function __set($var, $value){
        $this->output[$var] = $value;
    }

    /**
     * Perform request from client.
     */
    function execute(){
        // Check for an authentication key.
        // @todo - this happens in the beginning of index file - should we move it here?
        if($this->auth_key) {
            if(Request::get('auth_key') != $this->auth_key) {
                $this->_die('Invalid Auth Key');
            }
        }

        // Perform requested actions.
        if($actions = Request::get('actions', 'array')){
            $this->execute_actions($actions);
        }
    }

    private function execute_actions($actions){
        foreach($actions as $action){
            $function = explode(".",$action);
            try{
                // @todo parameterize this
                $action_file = $this->action_path.'/'.$function[0].'.php';
                // Make sure the file exists.
                if(!file_exists($action_file)) {
                    if($this->debug){
                        $this->_die('Missing Action File: '.$action_file);
                    } else {
                        $this->_die('Missing Action File');
                    }
                }
                require_once $action_file;
                $class = $function[0].'_actions';
                // Make sure the class exists.
                if(!class_exists($class)) {
                    if($this->debug){
                        $this->_die('Missing Action Class: '.$class);
                    } else {
                        $this->_die('Missing Action Class');
                    }
                }
                // Make sure the function exists.
                if(!method_exists($class, $function[1])) {
                    if($this->debug){
                        $this->_die('Missing Action: '.$function[1]);
                    } else {
                        $this->_die('Missing Action');
                    }
                }
                // Make sure method is static.
                // @todo
                if(FALSE) {
                    if($this->debug){
                        $this->_die('Method is not static: '.$function[1]);
                    } else {
                        $this->_die('Method is not static');
                    }
                }
                // Call method
                $class::$function[1]();
            } catch(Exception $e){
                if ($this->debug) {
                    global $errors;
                    $this->errors = array_merge($this->errors, $errors);
                }
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
    function _die($error='') {
        // These must be global to send to the foot file.
        // @todo fire some final callback

        if ($error){
            Messenger::error($error);
        }

        // Output errors and messages.
        if (count($this->errors) > 0) {
            $this->output['errors'] = Messenger::getErrors();
        }
        if (count($this->messages) > 0) {
            $this->output['messages'] = Messenger::getMessages();
        }

        // Call the shutdown function.
        if (!empty($this->shutdown_function) && is_callable($this->shutdown_function)) {
            call_user_func($this->shutdown_function, FALSE, FALSE);
            global $output;
            $this->output = array_merge($this->output, $output);
        }

        echo json_encode($this->output);
        exit;
    }
}
