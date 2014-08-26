<?php

namespace Lightning\CLI;

class CLI {
    public function execute() {
        global $argv;
        $func = 'execute' . str_replace(' ', '', ucfirst(str_replace('-', ' ', $argv[2])));
        if (method_exists($this, $func)) {
            $this->$func();
        }
        else {
            echo "No handler found.\n";
        }
    }
}
