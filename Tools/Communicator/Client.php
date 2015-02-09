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

/**
 * Makes a fast call to an API server using curl
 *
 * @package Lightning\Tools\API
 */
class Client {

    protected $vars;
    protected $results;
    protected $additional_data = array();
    protected $debug = false;
    protected $json = false;
    protected $auto_template = array();
    protected $last_action = NULL;
    protected $raw;
    protected $status;
    protected $cookies = array();
    protected $sendJSON = false;

    protected $server_address;
    protected $server_key;

    /**
     * Initialize some vars.
     */
    function __construct($server_address, $server_key){
        $this->server_address = $server_address;
        $this->server_key = $server_key;
        $this->vars['actions'] = array();

        // Check if there is an xdebug request:
        if ($this->forwardCookies) {

        }
    }

    /**
     * Adds an additional action to call when connection is executed.
     *
     * @param $action
     */
    function action($action){
        $this->vars['actions'][] = $action;
    }


    /**
     * set function.
     *
     * @access public
     * @param mixed $var
     * @param mixed $value
     * @param bool $auto_pass_to_template (default: false)
     *   If this is set, then the variable passed is also expected to be returned and will automatically be added to the template.
     * @return void
     */
    function set($var, $value, $auto_pass_to_template=false){
        $this->vars[$var] = $value;
        if($auto_pass_to_template)
            $this->auto_template[] = $var;
    }

    /**
     * Gets a var from the returned data.
     *
     * @param $var
     * @return null
     */
    function get($var){
        if(isset($this->results[$var]))
            return $this->results[$var];
        return NULL;
    }

    function get_errors() {
        if (!isset($this->results['errors'])) {
            return array();
        }
        else {
            return $this->results['errors'];
        }
    }

    /**
     * Returns an associative array of all data returned.
     *
     * @return mixed
     */
    function getResults(){
        return $this->results;
    }

    /**
     * Returns true if the last request returned errors.
     *
     * @return bool
     */
    function has_errors(){
        if(isset($this->results['errors']) && count($this->results['errors']) > 0)
            return true;
        return false;
    }

    /**
     * If there was an action called at execution time, this will show it.
     */
    function print_last_action(){
        if($this->last_action) { echo $this->last_action; }
    }

    /**
     * Adds a request for additional data.
     * @todo - can this be replaced now that action is an array?
     *
     * @param $var
     */
    function load($var){
        $this->additional_data[] = $var;
    }

    function set_json($uses_json=true){
        $this->json = $uses_json;
    }

    function debug($debug_mode = true){
        $this->debug = $debug_mode;
    }

    /**
     * Connect to the URL and load the data.
     */
    private function connect($vars){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->server_address);
        curl_setopt($curl, CURLOPT_POST, 1);
        $content = $this->sendJSON ? json_encode($vars) : http_build_query($vars);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($this->cookies)) {
            curl_setopt($curl, CURLOPT_COOKIE, cookie_implode($this->cookies));
        }

        $this->raw = curl_exec($curl);
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    }

    protected function enableRemoteDebug($debug_string = null) {
        if ($debug_string) {
            $this->cookies['XDEBUG_SESSION'] = $debug_string;
        }
        elseif (!empty($_GET['XDEBUG_SESSION_START'])) {
            $this->cookies['XDEBUG_SESSION'] = $_GET['XDEBUG_SESSION_START'];
        }
        elseif (!empty($_COOKIE['XDEBUG_SESSION'])) {
            $this->cookies['XDEBUG_SESSION'] = $_COOKIE['XDEBUG_SESSION'];
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
    function call($action=NULL){
        try {
            // Compose all vars.
            $vars = $this->vars;
            // set the action
            if($action) {
                $vars['actions'][] = $action;
                $this->last_action = $action;
            }
            // Request additional data.
            $vars['additional_data'] = $this->additional_data;

            // Add the connection key.
            $vars['KEY'] = $this->server_key;

            // Connect to server.
            $this->connect($vars, $this->cookies);

            if($this->raw){
                $this->results = json_decode($this->raw, true);
                switch($this->status){
                    case 200:
                        // Complete success. Return result.
                        return $this->request_success();
                        break;
                    case 401:
                    case 402:
                    case 403:
                        // If access denied.
                        $this->request_forbidden($this->status);
                        break;
                    default:
                        // Unrecognized.
                        $this->_die("There was an error processing your request. Please try again later. (1)");
                }
                return false;
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            $this->_die("There was an error processing your request. Please try again later. (2)");
        }
    }

    function get_raw(){
        return $this->raw;
    }

    public function sendJSON($set = true) {
        $this->sendJSON = $set;
    }

    function _die($message, $error_code = 200){
        // @todo - this should be done in the _die function
        if($this->json){
            json_die($message, $error_code);
        } else {
            _die($message, $error_code);
        }
    }

    private function request_forbidden($status_code){
        if(count($_POST) > 0){
            // Temporary redirect to a page where there is no POST data.
            Navigation::redirect($_SERVER['REQUEST_URI'], 307);
        } else {
            // Output the access denied message.
            $this->_die($this->results['errors'][0], $status_code);
        }
    }

    private function request_success(){
        if(is_array($this->results)){
            // HEADERS
            if(isset($this->results['cookies']) && is_array($this->results['cookies'])){
                foreach($this->results['cookies'] as $cookie=>$params){
                    if($cookie == '') continue;
                    $value = isset($params[0]) ? $params[0] : '';
                    $expire = isset($params[1]) ? $params[1] : '';
                    $path = isset($params[2]) ? $params[2] : "/";
                    $domain = isset($params[3]) ? $params[3] : COOKIE_DOMAIN;
                    $https = !empty( $_SERVER['HTTPS'] ) ? TRUE : FALSE;
                    $http_only = true;
                    setcookie($cookie, $value, $expire, $path, $domain, $https, $http_only);
                }
            }

            if(isset($this->results['redirect'])){
                if(isset($this->results['set_redirect'])){
                    // bring them back to this page after
                    $qsa = strstr($this->results['redirect'], '?') ? '&' : '?';
                    $redirect = $this->results['redirect'].$qsa.'redirect='.urlencode($_SERVER['REQUEST_URI']);
                } else {
                    $redirect = $this->results['redirect'];
                }
                $this->_redirect($redirect);
            }

            // STANDARD OUTPUT
            if(isset($this->results['errors']) && is_array($this->results['errors'])){
                foreach ($this->results['errors'] as $error) {
                    Messenger::error($error);
                }
            }
            if(isset($this->results['messages']) && is_array($this->results['messages'])){
                foreach ($this->results['messages'] as $message) {
                    Messenger::message($message);
                }
            }

            return $this->has_errors() ? false : true;
        } else {
            if(TEST_ENVIRONMENT){
                $this->_die("Error reading from application!\n{$this->raw}");
            } else {
                $this->_die("Error reading from application!");
            }
        }
    }
}
