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
            call_user_func_array([$this, $func], $args);
        }
        else {
            $this->out('No handler found.');
        }
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
