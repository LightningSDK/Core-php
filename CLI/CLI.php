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
        $func = Request::convertFunctionName($argv[2], 'execute');
        if (method_exists($this, $func)) {
            $args = count($argv) > 3 ? array_slice($argv, 3) : [];
            $this->parseArgs($args);
            call_user_func_array([$this, $func], $args);
        }
        else {
            $this->out('No handler found.');
            foreach (Configuration::get('routes.cli_only') as $command => $class) {
                $this->out('  ' . $command);
            }
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
        foreach ($args as $key => $arg) {
            if (substr($arg, 0, 2) == '--') {
                if (strpos($arg, '=') !== false) {
                    $arg = explode('=', $arg, 2);
                    $this->parameters[substr($arg[0], 2)] = $arg[1];
                } else {
                    $next_arg = $args[$key + 1];
                    $this->parameters[substr($arg, 2)] = $next_arg;
                    next($args);
                }
            } elseif ($arg[0] == '-') {
                for ($i = 1; $i < strlen($arg); $i++) {
                    $this->flags[$arg[$i]] = true;
                }
            }
        }
    }

    /**
     * Get a param value from the input.
     *
     * @param array|string $params
     *   A param or list of params in the order they should be checked
     * @param mixed $default
     *   The default value if none is set.
     *
     * @return mixed
     *   The param value
     */
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
