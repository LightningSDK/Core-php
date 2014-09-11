<?php
/**
 * @file
 * Contains Lightning\Tools\API\Client
 */

namespace Lightning\Tools\API;

/**
 * Makes a fast call to an API server using curl
 *
 * @package Lightning\Tools\API
 */
class Client {

    private $vars;
    private $results;
    private $additional_data = array();
    private $debug = false;
    private $json = false;
    private $auto_template = array();
    private $last_action = NULL;
    private $raw;
    private $status;
    private $cookies;

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
        $this->cookies = array();
        if (!empty($_GET['XDEBUG_SESSION_START'])) {
            $this->cookies['XDEBUG_SESSION'] = $_GET['XDEBUG_SESSION_START'];
        }
        elseif (!empty($_COOKIE['XDEBUG_SESSION'])) {
            $this->cookies['XDEBUG_SESSION'] = $_COOKIE['XDEBUG_SESSION'];
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
    function get_all(){
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
     * Outputs all vars.
     */
    function print_vars(){
        print_r($this->vars);
    }

    /**
     * Outputs additional data request vars (names, not data)
     */
    function print_additional_data(){
        print_r($this->additional_data);
    }

    /**
     * Prints the raw response from the server.
     */
    function print_raw(){
        echo $this->raw;
    }

    /**
     * Prints decoded results.
     * @todo this can be removed now that we use curl?
     */
    function print_results(){
        print_r($this->results);
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
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($vars));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($this->cookies)) {
            curl_setopt($curl, CURLOPT_COOKIE, cookie_implode($this->cookies));
        }

        $this->raw = curl_exec($curl);
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
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
        $start = microtime(true);

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
                $this->results = json_decode($this->raw,true);
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

    function _redirect($location){
        if($this->json){
            json_redirect($location);
        } else {
            _redirect($location);
        }
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
            _redirect($_SERVER['REQUEST_URI'], 307);
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

            global $cli, $user, $template;
            if(isset($this->results['user']) && !$cli){
                $user = new user($this->results['user']);
                $template->set_reference('user',$user);
            }

            foreach($this->additional_data as $add){
                // Make sure it was returned.
                if(isset($this->results[$add])){
                    switch($add){
                        case 'assets':
                            global $assets;
                            require_once 'include/class_assets.php';
                            $assets = assets::get_instance($this->results['assets']);
                            $template->set_reference('assets',$assets);
                            break;
                        // HANDLED PREVIOUSLY
                        case 'accounts':
                            break;
                        case 'token':
                            $template->set_reference('token',$this->results['token']);
                        default:
                            $template->set($add,$this->results[$add]);
                    }
                } else {
                    // @todo log error?
                }
            }

            // SEND VARS TO TEMPLATE
            foreach($this->auto_template as $var){
                $template->set($var, $this->results[$var]);
            }

            // STANDARD OUTPUT
            if(isset($this->results['errors']) && is_array($this->results['errors'])){
                global $errors;
                $errors = array_merge($errors,$this->results['errors']);
            }
            if(isset($this->results['messages']) && is_array($this->results['messages'])){
                global $messages;
                $messages = array_merge($messages, $this->results['messages']);
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
