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
class Client {

    protected $vars;
    protected $results;
    protected $load = array();
    protected $debug = false;
    protected $json = false;
    protected $auto_template = array();
    protected $last_action = NULL;
    protected $raw;
    protected $status;
    protected $cookies = array();
    protected $sendJSON = false;

    protected $serverAddress;
    protected $forwardCookies = false;
    protected $verbose = false;

    /**
     * Initialize some vars.
     */
    function __construct($server_address){
        $this->serverAddress = $server_address;
        $this->vars['actions'] = array();
        $this->verbose = Configuration::get('debug', false);
    }

    public function forwardCookies($forward = true) {
        $this->forwardCookies = $forward;
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
        if(isset($this->results['data'][$var]))
            return $this->results['data'][$var];
        return NULL;
    }

    function getErrors() {
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
    public function getResults(){
        return $this->results;
    }

    /**
     * Returns true if the last request returned errors.
     *
     * @return bool
     */
    function hasErrors(){
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
    public function load($var){
        if (is_array($var)) {
            $this->load = array_merge($this->load, $var);
        } else {
            $this->load[] = $var;
        }
    }

    function set_json($uses_json=true){
        $this->json = $uses_json;
    }

    /**
     * Connect to the URL and load the data.
     */
    private function connect($vars){

        // Check if there is an xdebug request:
        if ($this->forwardCookies) {
            $this->cookies += $_COOKIE;
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->serverAddress);
        curl_setopt($curl, CURLOPT_POST, 1);
        $content = $this->sendJSON ? json_encode($vars) : http_build_query($vars);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($this->cookies)) {
            curl_setopt($curl, CURLOPT_COOKIE, $this->cookieImplode($this->cookies));
        }

        $this->raw = curl_exec($curl);
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    }

    protected function cookieImplode($cookies) {
        // @TODO: Does this need sanitization?
        $a2 = array();
        foreach ($cookies as $k => $v) {
            $a2[] = "{$k}={$v}";
        }
        return implode('; ', $a2);
    }

    /**
     * Connect and interpret response.
     *
     * @param null $action
     *   Shortcut to add an additional action to post at call time.
     * @return bool
     *   Returns true if no errors from the communicator server.
     */
    public function call($action=NULL){
        try {
            // Compose all vars.
            $vars = $this->vars;
            // set the action
            if($action) {
                $vars['actions'][] = $action;
                $this->last_action = $action;
            }
            // Request additional data.
            $vars['load'] = $this->load;

            // Connect to server.
            $this->connect($vars, $this->cookies);

            if($this->raw){
                $this->results = json_decode($this->raw, true);
                switch($this->status){
                    case 200:
                        // Complete success. Return result.
                        return $this->requestSuccess();
                        break;
                    case 401:
                    case 402:
                    case 403:
                        // If access denied.
                        $this->requestForbidden($this->status);
                        break;
                    default:
                        // Unrecognized.
                        if ($this->verbose) {
                            echo $this->raw;
                        }
                        $this->_die("There was an error processing your request. Please try again later. (1)");
                }
                return false;
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            $this->_die("There was an error processing your request. Please try again later. (2)");
        }
    }

    public function getRequestVars() {
        return $this->vars;
    }

    public function getRaw(){
        return $this->raw;
    }

    public function getAdditionalData() {
        return $this->load;
    }

    public function sendJSON($set = true) {
        $this->sendJSON = $set;
    }

    function _die($message, $error_code = 200){
        // @todo - this should be done in the output function
        if($this->json){
            Output::jsonError($message);
        } else {
            Output::error($message);
        }
    }

    private function requestForbidden($status_code){
        if(count($_POST) > 0){
            // Temporary redirect to a page where there is no POST data.
            Navigation::redirect($_SERVER['REQUEST_URI'], 307);
        } else {
            // Output the access denied message.
            $this->_die($this->results['errors'][0], $status_code);
        }
    }

    protected function requestSuccess(){
        if(is_array($this->results)){
            // HEADERS
            if(isset($this->results['cookies']) && is_array($this->results['cookies'])){
                foreach($this->results['cookies'] as $cookie=>$params){
                    if($cookie == '') continue;
                    $params += array(
                        'value' => null,
                        'ttl' => null,
                        'path' => null,
                        'domain' => null,
                        'secure' => null,
                        'httponly' => null,
                    );
                    setcookie($cookie, $params['value'], $params['ttl'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
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
                Navigation::redirect($redirect);
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

            return $this->hasErrors() ? false : true;
        } else {
            if($this->verbose){
                $this->_die("Error reading from application!\n{$this->raw}");
            } else {
                $this->_die("Error reading from application!");
            }
        }
    }
}
