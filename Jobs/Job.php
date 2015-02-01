<?php

namespace Lightning\Jobs;

use Lightning\Tools\Logger;

class Job {

    public $name;

    /**
     * In debug mode, this will output to the stdout.
     *
     * @var boolean
     */
    public $debug = false;

    public function execute($job) {
    }

    public function out($string) {
        if ($this->debug) {
            print $string . "\n";
        } else {
            Logger::message($string);
        }
    }
}
