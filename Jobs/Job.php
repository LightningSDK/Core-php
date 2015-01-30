<?php

namespace Lightning\Jobs;

use Lightning\Tools\Logger;

class Job {

    public $name;

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
