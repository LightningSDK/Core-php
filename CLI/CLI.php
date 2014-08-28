<?php
/**
 * @file
 * Contains Lightning\Tools\Request\CLI
 */

namespace Lightning\CLI;

use Lightning\Tools\Request;

/**
 * A base controller for CLI handlers.
 *
 * @package Lightning\CLI
 */
class CLI {
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
            echo "No handler found.\n";
        }
    }
}
