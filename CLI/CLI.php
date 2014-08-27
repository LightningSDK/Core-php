<?php

namespace Lightning\CLI;

use Lightning\Tools\Request;

class CLI {
    public function execute() {
        global $argv;
        $func = 'execute' . Request::convertFunctionName('execute', $argv[2]);
        if (method_exists($this, $func)) {
            $this->$func();
        }
        else {
            echo "No handler found.\n";
        }
    }
}
