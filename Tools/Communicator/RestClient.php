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
    protected $results;
    protected $debug = false;
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
    function set($var, $value, $auto_pass_to_template=false) {
        $this->vars[$var] = $value;
        if ($auto_pass_to_template)
            $this->auto_template[] = $var;
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

        // Check if there is an xdebug request:
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
        if ($post) {
            if ($this->sendJSON) {
                $content = json_encode($vars);
                curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            } else {
                $content = http_build_query($vars);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($this->cookies)) {
            curl_setopt($curl, CURLOPT_COOKIE, $this->cookieImplode($this->cookies));
        }

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
                    throw new Exception('Unrecognized response code: ' . $this->status);
            }
        }
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

    private function requestForbidden($status_code) {
        if (!empty($_POST) > 0) {
            // Temporary redirect to a page where there is no POST data.
            Navigation::redirect($_SERVER['REQUEST_URI'], 307);
        } else {
            // Output the access denied message.
            Output::error($this->results['errors'][0], $status_code);
        }
    }

    protected function requestSuccess() {
        if (is_array($this->results)) {
            // HEADERS
            if (isset($this->results['cookies']) && is_array($this->results['cookies'])) {
                foreach ($this->results['cookies'] as $cookie=>$params) {
                    if ($cookie == '') continue;
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

            if (isset($this->results['redirect'])) {
                if (isset($this->results['set_redirect'])) {
                    // bring them back to this page after
                    $qsa = strstr($this->results['redirect'], '?') ? '&' : '?';
                    $redirect = $this->results['redirect'].$qsa.'redirect='.urlencode($_SERVER['REQUEST_URI']);
                } else {
                    $redirect = $this->results['redirect'];
                }
                Navigation::redirect($redirect);
            }

            // STANDARD OUTPUT
            if (isset($this->results['errors']) && is_array($this->results['errors'])) {
                foreach ($this->results['errors'] as $error) {
                    Messenger::error($error);
                }
            }
            if (isset($this->results['messages']) && is_array($this->results['messages'])) {
                foreach ($this->results['messages'] as $message) {
                    Messenger::message($message);
                }
            }

            return $this->hasErrors() ? false : true;
        } else {
            if ($this->verbose) {
                Output::error("Error reading from application!\n{$this->raw}");
            } else {
                Output::error("Error reading from application!");
            }
        }
    }
}
