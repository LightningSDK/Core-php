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
class RestClient {

    protected $vars = array();
    protected $headers = array();
    protected $results;
    protected $debug = false;
    protected $auto_template = array();
    protected $last_action = NULL;
    protected $raw;
    protected $status;
    protected $cookies = array();
    protected $sendJSON = false;
    protected $sendData;

    protected $serverAddress;
    protected $forwardCookies = false;
    protected $verbose = false;

    /**
     * Initialize some vars.
     */
    function __construct($server_address) {
        $this->serverAddress = $server_address;
        $this->verbose = Configuration::get('debug', false);
    }

    public function forwardCookies($forward = true) {
        $this->forwardCookies = $forward;
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
    public function set($var, $value, $auto_pass_to_template=false) {
        $this->vars[$var] = $value;
        if ($auto_pass_to_template)
            $this->auto_template[] = $var;
    }

    public function setHeader($header, $value) {
        $this->headers[$header] = $value;
    }

    public function setBody($data) {
        $this->sendData = $data;
    }

    /**
     * Gets a var from the returned data.
     *
     * @param $var
     * @return null
     */
    function get($var) {
        if (isset($this->results[$var]))
            return $this->results[$var];
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
    public function getResults() {
        return $this->results;
    }

    /**
     * Returns true if the last request returned errors.
     *
     * @return bool
     */
    function hasErrors() {
        if (isset($this->results['errors']) && count($this->results['errors']) > 0)
            return true;
        return false;
    }

    /**
     * Connect to the URL and load the data.
     */
    protected function connect($vars, $post = true, $path = null) {

        $headers = [];

        // This is useful for forwarding an XDEBUG request to another server for debugging.
        if ($this->forwardCookies) {
            $this->cookies += $_COOKIE;
        }

        $curl = curl_init();
        $url = $path
            ? preg_replace('|([^:])/+|i', '$1/', $this->serverAddress . '/' . $path)
            : $this->serverAddress;
        if (!$post) {
            $concat_char = strpos($url, '?') ? '&' : '?';
            $url .= $concat_char . http_build_query($vars);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, (int) $post);
        $headers[] = 'Accept: application/json';
        foreach ($this->headers as $h => $v) {
            $headers[] = $h . ': ' . $v;
        }
        if ($post) {
            if ($this->sendJSON) {
                $content = json_encode($vars);
                $headers[] = 'Content-Type: application/json';
            } elseif (!empty($this->sendData)) {
                $content =& $this->sendData;
            } else {
                $content = http_build_query($vars);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($this->cookies)) {
            curl_setopt($curl, CURLOPT_COOKIE, $this->cookieImplode($this->cookies));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $this->raw = curl_exec($curl);
        $this->status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    }

    protected function cookieImplode($cookies) {
        // @TODO: Does this need sanitization?
        $a2 = array();
        foreach ($cookies as $k => $v) {
            $a2[] = "{$k}={$v}";
        }
        return implode('; ', $a2);
    }

    public function callGet($path = null) {
        $this->connect($this->vars, false, $path);
        return $this->processResponse();
    }

    public function callPost($path = null) {
        $this->connect($this->vars, true, $path);
        return $this->processResponse();
    }

    protected function processResponse() {
        if ($this->raw) {
            $this->results = json_decode($this->raw, true);
            switch($this->status) {
                case 200:
                    // If there is a success callback.
                    return $this->requestSuccess();
                    break;
                case 401:
                case 402:
                case 403:
                    // If there is an error handler.
                    return $this->requestForbidden($this->status);
                    break;
            }
        }
        // Unrecognized.
        if ($this->verbose) {
            echo $this->raw;
        }
        throw new Exception('Unrecognized response code: ' . $this->status);
    }

    protected function requestSuccess() {
        return true;
    }

    protected function requestForbidden($status) {
        return false;
    }

    public function getRequestVars() {
        return $this->vars;
    }

    public function getRaw() {
        return $this->raw;
    }

    public function sendJSON($set = true) {
        $this->sendJSON = $set;
    }
}
