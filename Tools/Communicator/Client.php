<?php
/**
 * @file
 * Contains Lightning\Tools\API\Client
 */

namespace Lightning\Tools\Communicator;
use Exception;
use Lightning\Tools\Data;
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
        if (!empty($this->results['data'])) {
            return Data::getFromPath($var, $this->results['data']);
        }
    }

    function getAll() {
        return $this->results['data'];
    }

    /**
     * If there was an action called at execution time, this will show it.
     */
    function print_last_action() {
        if ($this->last_action) { echo $this->last_action; }
    }

    /**
     * Adds an additional action to call when connection is executed.
     *
     * @param $action
     */
    function action($action) {
        if (!isset($this->vars)) {
            $this->vars['actions'] = [];
        }
        $this->vars['actions'][] = $action;
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
            Output::error('There was an error processing your request. Please try again later. (2): ' . $e->getMessage());
        }
    }

    protected function requestSuccess() {
        if (is_array($this->results)) {
            // HEADERS
            $this->outputCookies();

            $this->redirect();

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

    protected function requestForbidden($status_code) {
        if (!empty($_POST) > 0) {
            // Temporary redirect to a page where there is no POST data.
            Navigation::redirect($_SERVER['REQUEST_URI'], 307);
        } else {
            // Output the access denied message.
            Output::error($this->results['errors'][0], $status_code);
        }
    }

    protected function outputCookies() {
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
                Output::setCookie($cookie, $params['value'], $params['ttl'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
        }
    }

    protected function redirect() {
        if (!empty($this->results['redirect'])) {
            if (!empty($this->results['set_redirect'])) {
                // bring them back to this page after
                $qsa = strstr($this->results['redirect'], '?') ? '&' : '?';
                $redirect = $this->results['redirect'] . $qsa . 'redirect=' . urlencode($_SERVER['REQUEST_URI']);
            } else {
                $redirect = $this->results['redirect'];
            }
            Navigation::redirect($redirect);
        }
    }

    public function getAdditionalData() {
        return $this->load;
    }
}
