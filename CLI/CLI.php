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
            $this->$func();
        }
        else {
            $this->out('No handler found.');
        }
    }

    public function readline($prompt = null) {
        if ($prompt) {
            echo $prompt;
        }
        $fp = fopen("php://stdin", "r");
        $line = rtrim(fgets($fp, 1024));
        return $line;
    }

    public function out($string) {
        Logger::message($string, true);
        print $string . "\n";
    }
}
