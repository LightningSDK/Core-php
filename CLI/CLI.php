<?php
/**
 * @file
 * Contains Lightning\Tools\Request\CLI
 */

namespace Lightning\CLI;

use Lightning\Tools\Configuration;
use Lightning\Tools\Logger;
use Lightning\Tools\Request;

/**
 * A base controller for CLI handlers.
 *
 * @package Lightning\CLI
 */
class CLI {

    protected $flags = [];
    protected $parameters = [];

    /**
     * Create the object and set the logger.
     */
    public function __construct() {
        Logger::setLog(Configuration::get('cli.log'));
    }

    /**
     * The main execute method called from index.php
     */
    public function execute() {
        global $argv;
        $func = Request::convertFunctionName('execute', $argv[2]);
        if (method_exists($this, $func)) {
            $args = count($argv) > 3 ? array_slice($argv, 3) : [];
            $this->parseArgs($args);
            call_user_func_array([$this, $func], $args);
        }
        else {
            $this->out('No handler found.');
        }
    }

    /**
     * Parse the incoming CLI arguments into flags and parameters.
     *
     * @param array $args
     *   The arguments from the command line, excluding those already processed.
     *
     * The following formats will be parsed:
     *   -x123 will set parameter x = 123
     *   -x 123 will set parameter x = 123
     *   --var 123 will set param var = 123
     *   --var will set flag var = true
     */
    protected function parseArgs($args) {
        print_r($args);
        foreach ($args as $key => $arg) {
            if (substr($arg, 0, 2) == '--') {
                echo '.--.';
                $next_arg = $args[$key + 1];
                echo 'next=' . $next_arg;
                if ($next_arg[0] == '-') {
                    $this->flags[substr($arg, 2)] = true;
                } else {
                    $this->parameters[substr($arg, 2)] = $next_arg;
                    next($args);
                }
            } elseif ($arg[0] == '-') {
                echo '.-.';
                if (strlen($arg) > 2) {
                    $this->parameters[$arg[1]] = substr($arg, 2);
                } else {
                    // Get the value form the parameter after
                    $this->parameters[$arg[1]] = $args[$key + 1];
                    next($args);
                }
            }
        }
        print_r($this->parameters);
        print_r($this->flags);
    }

    protected function get($params, $default = null) {
        if (is_array($params)) {
            foreach ($params as $p) {
                if (isset($this->parameters[$p])) {
                    return $this->parameters[$p];
                }
            }
            return $default;
        }
        if (isset($this->parameters[$params])) {
            return $this->parameters[$params];
        }
        return $default;
    }

    /**
     * Get a line from the CLI input.
     *
     * @param string $prompt
     *   A line to prompt the user.
     * @param boolean $password
     *   Whether to hide the input for private data.
     *
     * @return string
     *   Return the user input.
     */
    public function readline($prompt = null, $password = false) {
        if ($prompt) {
            echo $prompt;
        }
        if ($password) {
            system('stty -echo');
        }
        $fp = fopen('php://stdin', 'r');
        $line = rtrim(fgets($fp, 1024));
        if ($password) {
            system('stty echo');
            echo "\n";
        }
        return $line;
    }

    /**
     * Print a line to the stdout.
     *
     * @param string $string
     *   The output.
     * @param boolean $log
     *   Whether to add the output to the log.
     */
    public function out($string, $log = false) {
        if ($log) {
            Logger::message($string);
        }
        print $string . "\n";
    }
}
